<?php
	if(!defined('BASEPATH'))
		exit('No direct script access allowed');


	/**
 * Payroll model - Now Sync-Aware with Enhanced Security
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 * 
 * ENHANCED FEATURES (Tasks 7.1-7.8):
 * - Field whitelisting for security
 * - Input validation with Payroll_validator
 * - Database transaction wrapper with rollback
 * - Audit logging integration
 * - Automatic PAYE calculation with Tax_calculator
 * - Duplicate payment prevention
 * - Copy from last month functionality
 */
class Payroll_model extends MY_Model {

		/**
		 * Whitelisted fields for payroll data (Task 7.1 - Field Whitelisting)
		 * Only these fields can be set via user input
		 */
		private $allowed_fields = [
			'employee_code', 'year', 'month', 'basic_salary',
			'market_premium_allowance', 'teaching_allowance', 'responsibility_allowance',
			'extra_class_allowance', 'rural_allowance', 'other_allowances',
			'ssnit', 'income_tax', 'petra', 'tier2_contribution', 'tier2_provider_id', 'get_fund', 'salary_advance',
			'nhil', 'loan', 'welfare_dues', 'gnat_dues', 'other_deductions',
			'working_days', 'days_present', 'days_absent',
			'total_allowances', 'total_deductions', 'gross_salary', 'net_salary',
			'employment_category', 'reference', 'approval_status', 'status',
			// Statutory rates snapshot (saved at time of payroll creation)
			'rate_ssnit_tier1_employer', 'rate_ssnit_tier1_employee', 'rate_ssnit_tier2', 
			'rate_getfund', 'rate_nhil', 'paid_by'
		];

		function __construct() {

			parent::__construct();
			// Load required libraries and models
			$this->load->library('Payroll_validator');
			$this->load->library('Tax_calculator');
			$this->load->model('Audit_log_model', 'audit_log');
			$this->load->model('Sms_model', 'sms_model');
			
			// Clear tax brackets cache to ensure latest rates are loaded from database
			$this->tax_calculator->clear_cache();
		}

		/**
		 * Store payroll data with enhanced security and validation
		 * 
		 * ENHANCEMENTS:
		 * - Task 7.1: Field whitelisting
		 * - Task 7.2: Input validation
		 * - Task 7.3: Transaction wrapper
		 * - Task 7.4: Rollback logic
		 * - Task 7.5: Audit logging
		 * - Task 7.6: Auto PAYE calculation
		 * - Task 7.7: Duplicate check
		 * 
		 * @param string $payroll_id Optional payroll ID for updates
		 * @return mixed True on success, error message string on failure, array with validation errors
		 */
		public function store($payroll_id = '') {

			$payrollMonthYear = trim($this->input->post('payrollMonth'));
			$payrollMonthYearArray = explode('-', $payrollMonthYear);

			$payrollYear = $payrollMonthYearArray[0];
			$payrollMonth = getMonthInWords($payrollMonthYearArray[1]);

			$employee_codes = explode(',', $this->input->post('staffData'));

			if(count($employee_codes) < 1) {
				return 'No staff selected';
			}

			$batchDataInsert = array();
			$batchDataUpdate = array();

			$sms_data_array = array();
			$currency = get_settings('currency');
			
			// Get active SMS service
			$active_sms_service = $this->db->get_where('settings', ['type' => 'active_sms_service'])->row()->description ?? 'disabled';
			
			// Get current user ID
			$current_user_id = $this->session->userdata('login_user_id') ?? $this->session->userdata('admin_id');

			// Task 7.3: Start transaction wrapper
			$this->db->trans_start();
			
			// Load statutory rates ONCE before loop (not inside loop for performance)
			$this->load->model('Payroll_statutory_model');
			$current_rates = $this->Payroll_statutory_model->get_rates_array();
			
			try {

				for($i = 0; $i < count($employee_codes); $i++):

	                $stringPos = strpos($employee_codes[$i], '_');
	                $table = substr($employee_codes[$i], 0, $stringPos);
	                $staff_code = substr($employee_codes[$i], $stringPos + 1);
	                
	                // FIX: Map both 'administrator' and 'admin' to 'admin' table name for database queries
	                // But preserve the employment_category as 'administrator' for validation
	                $actual_table = (in_array($table, ['administrator', 'admin'])) ? 'admin' : $table;
	                $table_code_field = $actual_table . '_code';

	                $randomNumber = random_int(100000, 999999);
	                $reference = $randomNumber = 'P'.$payrollYear.'/'.$payrollMonthYearArray[1].'/'. substr(strtotime('now'), -3).random_int(1000, 9999);


					$data['employee_code'] = $staff_code;
					$data['year'] = $payrollYear;
					$data['month'] = $payrollMonth;
					$data['basic_salary'] = trim($this->input->post('basicSalary'));
					$data['market_premium_allowance'] = trim($this->input->post('marketPremium'));
					$data['teaching_allowance'] = trim($this->input->post('teachingAllowance'));
					$data['responsibility_allowance'] = trim($this->input->post('responsibilityAllowance'));
					$data['extra_class_allowance'] = trim($this->input->post('extraClasses'));
					$data['rural_allowance'] = trim($this->input->post('ruralAllowance'));
					$data['other_allowances'] = trim($this->input->post('otherAllowances'));
					$data['ssnit'] = trim($this->input->post('ssnit'));
					$data['income_tax'] = trim($this->input->post('incomeTax'));
					$data['tier2_contribution'] = trim($this->input->post('petra'));  // Input field named 'petra' for backward compatibility
					$data['tier2_provider_id'] = trim($this->input->post('tier2_provider_id'));  // Task 23.3: Save Tier 2 provider selection
					$data['get_fund'] = trim($this->input->post('getfund'));
					$data['salary_advance'] = trim($this->input->post('salaryAdvance'));
					$data['nhil'] = trim($this->input->post('nhil'));
					$data['loan'] = trim($this->input->post('loans'));
					$data['welfare_dues'] = trim($this->input->post('welfare'));
					$data['gnat_dues'] = trim($this->input->post('gnat'));
					$data['other_deductions'] = trim($this->input->post('otherDeductions'));
					$data['working_days'] = trim($this->input->post('workingDays'));
					$data['days_present'] = trim($this->input->post('daysPresent'));
					$data['days_absent'] = trim($this->input->post('daysAbsent'));
					$data['total_allowances'] = trim($this->input->post('totalAllowancesForDb'));
					$data['total_deductions'] = trim($this->input->post('totalDeductionsForDb'));
					$data['gross_salary'] = trim($this->input->post('grossSalaryForDb'));
					$data['net_salary'] = trim($this->input->post('netSalaryForDb'));
					$data['paid_by'] = $current_user_id;
					// FIX: Map 'admin' table back to 'administrator' employment category for validator
					$data['employment_category'] = ($actual_table === 'admin') ? 'administrator' : $actual_table;
					$data['reference'] = $reference;
					$data['approval_status'] = 'paid'; // Automatically mark as paid - skip approval workflow
					$data['status'] = 'paid'; // Set status to paid as well
					
					// Snapshot statutory rates at time of payroll creation for historical accuracy
					// (Rates loaded once before loop for performance)
					$data['rate_ssnit_tier1_employer'] = $current_rates['ssnit_tier1_employer'];
					$data['rate_ssnit_tier1_employee'] = $current_rates['ssnit_tier1_employee'];
					$data['rate_ssnit_tier2'] = $current_rates['ssnit_tier2'];
					$data['rate_getfund'] = $current_rates['getfund'];
					$data['rate_nhil'] = $current_rates['nhil'];
					
					// Task 7.1: Apply field whitelisting
					$data = $this->apply_field_whitelist($data);
					
					// Task 7.2: Validate payroll data before processing
					$validation_errors = $this->payroll_validator->validate_payroll_data($data);
					
					if (!empty($validation_errors)) {
						// Task 7.4: Rollback on validation failure
						$this->db->trans_rollback();
						return [
							'success' => false,
							'message' => 'Validation errors found',
							'errors' => $validation_errors
						];
					}
					
					// Task 24.1: Validate Tier 2 provider selection
					$provider_validation = $this->validate_tier2_provider($data);
					if (!$provider_validation['valid']) {
						$this->db->trans_rollback();
						return [
							'success' => false,
							'message' => $provider_validation['error']
						];
					}
					
					// Store before data for audit logging
					$before_data_map = array(); // Map of pay_id => before_data

					if(!empty($payroll_id) || $payroll_id !== '') {

	                	// Task 7.7: Check for duplicates before update
	                	$this->db->where('employee_code', $staff_code);
	                	$this->db->where('month', $payrollMonth);
	                	$this->db->where('year', $payrollYear);
	                	$query = $this->db->get('pay_salary');

	                	if($query->num_rows() > 0) {
	                		/*exists so we do update*/
	                		$existing = $query->row_array();
	                		$data['pay_id'] = $existing['pay_id'];
	                		$before_data_map[$existing['pay_id']] = $existing; // Store for audit log with pay_id as key

	                		$batchDataUpdate[] = $data;/*prepare for updates*/

	                	} else { /*new staff was added to the list so we do insertion*/

	                		// Task 7.7: Duplicate check for new insertions
	                		$duplicate_check = $this->check_duplicate_payroll($staff_code, $payrollMonth, $payrollYear);
	                		if ($duplicate_check['exists']) {
	                			$this->db->trans_rollback();
	                			return [
	                				'success' => false,
	                				'message' => 'Duplicate payroll detected',
	                				'duplicate' => $duplicate_check['record']
	                			];
	                		}

	                		$batchDataInsert[] = $data;
	                	}


	                } else { /*new insertion*/

	                	// Task 7.7: Duplicate check for new insertions
                		$duplicate_check = $this->check_duplicate_payroll($staff_code, $payrollMonth, $payrollYear);
                		if ($duplicate_check['exists']) {
                			$this->db->trans_rollback();
                			return [
                				'success' => false,
                				'message' => 'Duplicate payroll detected',
                				'duplicate' => $duplicate_check['record']
                			];
                		}

	                	$batchDataInsert[] = $data;
	                }

	                /*preparing sms*/
	                $staff_row = $this->db->get_where($actual_table, [$table_code_field => $staff_code])->row();
	                
	                // Check if staff record exists and has phone
	                if (!$staff_row) {
	                	log_message('error', 'Payroll SMS Error: Staff not found - ' . $staff_code);
	                	continue; // Skip SMS for this staff but continue processing
	                }
	                
	                $staffPhone = isset($staff_row->phone) ? $staff_row->phone : '';
	                
	                // Only add to SMS array if phone number exists
	                if (!empty($staffPhone)) {
	                	// Build SMS message dynamically
	                	$smsMessage = 'Your salary of '.$currency.' '.number_format($data['net_salary'], 2, '.', ',').' for '.$payrollMonth .' '.$payrollYear. ' has been processed and will be credited to your account shortly. Basic salary: '.$currency.' '.number_format($data['basic_salary'], 2, '.', ',');
	                	
	                	// Add allowances if any
	                	if ($data['total_allowances'] > 0) {
	                		$smsMessage .= ', allowances: '.$currency.' '.number_format($data['total_allowances'], 2, '.', ',');
	                	}
	                	
	                	// Add deductions only if any
	                	if ($data['total_deductions'] > 0) {
	                		$smsMessage .= ', deductions: '.$currency.' '.number_format($data['total_deductions'], 2, '.', ',');
	                	}
	                	
	                	$smsMessage .= '. Ref: '.$reference.'. Thank you.';

	                	// FIX: Use 'Recipient' key instead of 'To' for send_sms_batch_personalized compatibility
	                	$sms_data_array[$i]['Recipient'] = $staffPhone;
						$sms_data_array[$i]['Content'] = $smsMessage;
					} else {
						log_message('error', 'Payroll SMS Error: No phone number for staff - ' . $staff_code);
					}


				endfor;

				$updateResult = false;
				$insertResult = false;

				if(count($batchDataUpdate) > 0) {
					/*update*/
					$updateResult = $this->db->update_batch('pay_salary', $batchDataUpdate, 'pay_id');
					
					// Task 27.3: Add audit logging on payroll update - compare before/after and log changed fields
					if ($updateResult) {
						foreach ($batchDataUpdate as $updated_record) {
							$pay_id = $updated_record['pay_id'];
							
							// Get the before data from our map (captured BEFORE update)
							$before_data = isset($before_data_map[$pay_id]) ? $before_data_map[$pay_id] : null;
							
							if ($before_data) {
								// Compare each field and log only changed fields
								foreach ($updated_record as $field => $new_value) {
									if (isset($before_data[$field]) && $before_data[$field] != $new_value) {
										// Log individual field change
										$this->log_audit(
											$pay_id,
											'update',
											$field,
											$before_data[$field],
											$new_value
										);
									}
								}
							}
						}
					}
				}

				if(count($batchDataInsert) > 0) {
					/*insert*/
					$insertResult = $this->db->insert_batch('pay_salary', $batchDataInsert);
					
					// Task 27.2: Add audit logging on payroll create using log_audit method
					if ($insertResult) {
						// Get the last insert IDs
						$insert_id = $this->db->insert_id();
						
						foreach ($batchDataInsert as $index => $inserted_record) {
							$pay_id = $insert_id + $index;
							
							// Log the complete payroll creation
							// field_changed = 'all_fields' for create operations
							// old_value = null (no previous data)
							// new_value = complete payroll data as JSON
							$this->log_audit(
								$pay_id,
								'create',
								'all_fields',
								null,
								json_encode($inserted_record)
							);
						}
					}
				}


				if($updateResult && $insertResult) {

					$result = true;

				} else if($updateResult || $insertResult) {

					$result = true;

				} else {

					$result = false;
				}
				
				// Task 7.3: Complete transaction
				$this->db->trans_complete();
				
				// Task 7.4: Check transaction status and rollback if failed
				if ($this->db->trans_status() === FALSE) {
					$this->db->trans_rollback();
					log_message('error', 'Payroll store transaction failed');
					return [
						'success' => false,
						'message' => 'Database transaction failed. Payroll not saved.'
					];
				}
				
				// Task 10.10: Invalidate dashboard cache after successful payroll create/update
				$this->load->driver('cache', array('adapter' => 'file'));
				$cache_key = "payroll_dashboard_{$payrollMonthYearArray[1]}_{$payrollYear}";
				$this->cache->delete($cache_key);

				if($result == true) {
					/*send sms*/
					if($active_sms_service != 'disabled') {
						/*We can send sms*/
						
						// Check if we have valid SMS recipients
						if (!empty($sms_data_array)) {
							try {
								log_message('info', 'Payroll SMS: Attempting to send ' . count($sms_data_array) . ' SMS messages');
								$response = $this->sms_model->send_sms_batch_personalized($sms_data_array);
								
								// Log successful SMS sending
								if (isset($response) && $response === 0) {
									log_message('info', 'Payroll SMS: Successfully sent ' . count($sms_data_array) . ' SMS messages');
								} else {
									log_message('error', 'Payroll SMS: Failed with response code: ' . json_encode($response));
								}
							} catch (Exception $e) {
								log_message('error', 'Payroll SMS Exception: ' . $e->getMessage());
								// Don't fail the operation if SMS fails
							}
						} else {
							log_message('warning', 'Payroll SMS: No valid recipients found for SMS (check phone numbers)');
						}
						
					} else {
						log_message('info', 'Payroll SMS: SMS service is disabled');
					}
				}

				return $result;
				
			} catch (Exception $e) {
				// Task 7.4: Rollback on exception
				$this->db->trans_rollback();
				log_message('error', 'Payroll store exception: ' . $e->getMessage());
				return [
					'success' => false,
					'message' => 'An error occurred: ' . $e->getMessage()
				];
			}
		}
		
		/**
		 * Task 7.1: Apply field whitelisting
		 * 
		 * Removes any fields not in the allowed list to prevent mass assignment vulnerabilities
		 * 
		 * @param array $data Input data
		 * @return array Filtered data with only whitelisted fields
		 */
		private function apply_field_whitelist($data) {
			$filtered_data = [];
			
			foreach ($this->allowed_fields as $field) {
				if (isset($data[$field])) {
					$filtered_data[$field] = $data[$field];
				}
			}
			
			return $filtered_data;
		}
		
		/**
		 * Task 1.1: Calculate SSNIT Tier 1 and Tier 2 on basic salary
		 * 
		 * CRITICAL BUSINESS LOGIC:
		 * - Tier 1 (13.5%) is EMPLOYER contribution - NOT deducted from employee
		 * - Tier 2 (5%) is EMPLOYEE contribution - deducted from gross salary
		 * 
		 * Both calculations are based on BASIC SALARY ONLY, not gross salary
		 * 
		 * @param float $basic_salary Basic salary amount
		 * @return array ['tier1' => float, 'tier2' => float, 'tier1_rate' => 13.5, 'tier2_rate' => 5.0]
		 */
		public function calculate_ssnit_tier1_tier2($basic_salary) {
			// Load statutory model if not already loaded
			if (!isset($this->payroll_statutory_model)) {
				$this->load->model('Payroll_statutory_model');
			}
			
			// Get current rates from database
			$rates = $this->Payroll_statutory_model->get_rates_array();
			
			// Calculate using dynamic rates (rates are stored as percentages, e.g., 13.5)
			$tier1_rate = $rates['ssnit_tier1_employer'] / 100;
			$tier2_rate = $rates['ssnit_tier2'] / 100;
			
			$tier1 = $basic_salary * $tier1_rate;  // Employer contribution
			$tier2 = $basic_salary * $tier2_rate;  // Employee contribution
			
			return [
				'tier1' => round($tier1, 2),
				'tier2' => round($tier2, 2),
				'tier1_rate' => $rates['ssnit_tier1_employer'],
				'tier2_rate' => $rates['ssnit_tier2']
			];
		}
		
		/**
		 * Task 2.1: Calculate gross and net salary with correct SSNIT treatment
		 * 
		 * CRITICAL BUSINESS LOGIC:
		 * - Gross Salary = Basic Salary + Total Allowances
		 * - Net Salary = Gross Salary - (Tier 2 + Other Deductions)
		 * - **IMPORTANT**: Tier 1 is EMPLOYER contribution and is NOT deducted from employee pay
		 * 
		 * 
		 * @param array $payroll_data Array with keys:
		 *   - 'basic_salary': Basic salary amount (float)
		 *   - 'total_allowances': Sum of all allowances (float)
		 *   - 'tier2': SSNIT Tier 2 employee contribution (float)
		 *   - 'total_other_deductions': All other deductions (tax, loans, etc.) (float)
		 * @return array ['gross_salary' => float, 'net_salary' => float]
		 */
		public function calculate_gross_net_salary($payroll_data) {
			$basic = $payroll_data['basic_salary'];
			$allowances = $payroll_data['total_allowances'];
			$tier2 = $payroll_data['tier2'];
			$other_deductions = $payroll_data['total_other_deductions'];
			
			// Gross = Basic + All Allowances
			$gross = $basic + $allowances;
			
			// Net = Gross - (Tier 2 + Other Deductions)
			// NOTE: Tier 1 is NOT deducted from employee pay
			$net = $gross - ($tier2 + $other_deductions);
			
			return [
				'gross_salary' => round($gross, 2),
				'net_salary' => round($net, 2)
			];
		}
		
		/**
		 * Task 7.6: Calculate PAYE for payroll using Tax_calculator
		 * 
		 * Automatically calculates Ghana PAYE based on gross salary and SSNIT deductions
		 * 
		 * @param float $gross_salary Monthly gross salary
		 * @param float $ssnit_deduction Monthly SSNIT deduction (Tier 1 + Tier 2)
		 * @return array ['monthly_paye' => float, 'breakdown' => array]
		 */
		public function calculate_paye_for_payroll($gross_salary, $ssnit_deduction) {
			try {
				$monthly_paye = $this->tax_calculator->calculate_monthly_paye($gross_salary, $ssnit_deduction);
				$breakdown = $this->tax_calculator->get_tax_breakdown();
				
				return [
					'success' => true,
					'monthly_paye' => $monthly_paye,
					'breakdown' => $breakdown
				];
			} catch (Exception $e) {
				log_message('error', 'PAYE calculation failed: ' . $e->getMessage());
				return [
					'success' => false,
					'message' => 'PAYE calculation failed: ' . $e->getMessage()
				];
			}
		}
		
		/**
		 * Task 7.7: Check for duplicate payroll
		 * 
		 * Prevents duplicate payment for same employee, month, and year
		 * 
		 * @param string $employee_code Employee code
		 * @param string $month Month name
		 * @param string $year Year
		 * @return array ['exists' => bool, 'record' => array|null]
		 */
		public function check_duplicate_payroll($employee_code, $month, $year) {
			$this->db->where('employee_code', $employee_code);
			$this->db->where('month', $month);
			$this->db->where('year', $year);
			$query = $this->db->get('pay_salary');
			
			if ($query->num_rows() > 0) {
				return [
					'exists' => true,
					'record' => $query->row_array()
				];
			}
			
			return ['exists' => false, 'record' => null];
		}
		
		/**
		 * Task 7.8: Copy payroll from last month
		 * 
		 * Retrieves previous month's payroll data for an employee to pre-fill form
		 * 
		 * @param string $employee_code Employee code
		 * @return array|null Previous month's payroll data or null if not found
		 */
		public function copy_from_last_month($employee_code) {
			// Get the most recent payroll for this employee
			$this->db->where('employee_code', $employee_code);
			$this->db->order_by('year', 'DESC');
			$this->db->order_by('FIELD(month, "December", "November", "October", "September", "August", "July", "June", "May", "April", "March", "February", "January")', 'DESC');
			$this->db->limit(1);
			$query = $this->db->get('pay_salary');
			
			if ($query->num_rows() > 0) {
				$last_payroll = $query->row_array();
				
				// Remove fields that should not be copied
				unset($last_payroll['pay_id']);
				unset($last_payroll['month']);
				unset($last_payroll['year']);
				unset($last_payroll['reference']);
				unset($last_payroll['approval_status']);
				unset($last_payroll['status']);
				unset($last_payroll['created_at']);
				unset($last_payroll['updated_at']);
				unset($last_payroll['sync_status']);
				unset($last_payroll['last_modified_at']);
				unset($last_payroll['device_id']);
				unset($last_payroll['last_modified_by']);
				
				return $last_payroll;
			}
			
			return null;
		}
		
		/**
		 * Task 24.1: Validate Tier 2 provider selection
		 * 
		 * Provider is optional when Tier 2 contribution is zero
		 * Provider is required when Tier 2 > 0
		 * 
		 * @param array $payroll_data Payroll data with tier2_contribution and tier2_provider_id
		 * @return array ['valid' => bool, 'error' => string|null]
		 */
		public function validate_tier2_provider($payroll_data) {
			$tier2 = isset($payroll_data['tier2_contribution']) ? floatval($payroll_data['tier2_contribution']) : 0;
			$provider_id = isset($payroll_data['tier2_provider_id']) ? intval($payroll_data['tier2_provider_id']) : 0;
			
			// Task 24.1: Provider is optional when Tier 2 = 0
			if ($tier2 == 0) {
				return ['valid' => true, 'error' => null];
			}
			
			// Provider is required when Tier 2 > 0
			if ($tier2 > 0 && empty($provider_id)) {
				return [
					'valid' => false,
					'error' => 'Tier 2 provider is required when Tier 2 contribution is greater than zero'
				];
			}
			
			// Verify provider exists and is active
			if ($provider_id > 0) {
				$this->db->where('provider_id', $provider_id);
				$this->db->where('is_active', 1);
				$provider = $this->db->get('pension_tier2_providers')->row();
				
				if (!$provider) {
					return [
						'valid' => false,
						'error' => 'Selected Tier 2 provider is invalid or inactive'
					];
				}
			}
			
			return ['valid' => true, 'error' => null];
		}

		function getPayrollFormByStaffCode($staff_code, $month, $year) {

			$this->db->where('employee_code', $staff_code);
			$this->db->where('month', $month);
			$this->db->where('year', $year);
			$query = $this->db->get('pay_salary');

			return $query->result();

		}

		function getAllPayroll() {

			$this->db->select('pay_salary.*, COALESCE(pay_salary.approval_status, "draft") as approval_status');
			$query = $this->db->get('pay_salary');
			return $query->result_array();

		}

		function getStaffPayroll($staffCode) {

			$this->db->select('pay_salary.*, COALESCE(pay_salary.approval_status, "draft") as approval_status');
			$this->db->where('employee_code', $staffCode);
			$query = $this->db->get('pay_salary');
			return $query->result_array();

		}
		
		/**
		 * Task 17.2: Override delete method to invalidate dashboard cache
		 * 
		 * Extends parent delete() method with cache invalidation
		 * 
		 * @param int $id Primary key value (pay_id)
		 * @return bool Success status
		 */
		public function delete($id) {
			// Get payroll record before deletion for cache key
			$this->db->where('pay_id', $id);
			$payroll = $this->db->get('pay_salary')->row();
			
			// Call parent delete method
			$result = parent::delete($id);
			
			// Task 17.2: Invalidate dashboard cache after successful deletion
			if ($result && $payroll) {
				$this->load->driver('cache', array('adapter' => 'file'));
				
				// Convert month name to number for cache key
				$month_map = ['January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'];
			$month_number = isset($month_map[$payroll->month]) ? $month_map[$payroll->month] : '01';
				$cache_key = "payroll_dashboard_{$month_number}_{$payroll->year}";
				$this->cache->delete($cache_key);
			}
			
			return $result;
		}
		
		/**
		 * Task 27.1: Log audit trail for payroll operations
		 * 
		 * Captures comprehensive audit information including user, action, and data changes
		 * 
		 * @param int $pay_id Payroll record ID
		 * @param string $action Action type (create, update, approve, reject, submit, delete)
		 * @param string|null $field_changed Field that was changed (for updates)
		 * @param string|null $old_value Old value before change
		 * @param string|null $new_value New value after change
		 * @return bool Success status
		 */
		public function log_audit($pay_id, $action, $field_changed = null, $old_value = null, $new_value = null) {
			// Task 27.1: Capture user_id from session
			$user_id = $this->session->userdata('login_user_id') ?? $this->session->userdata('admin_id');
			
			// Task 27.1: Capture IP address and user agent
			$ip_address = $this->input->ip_address();
			$user_agent = $this->input->user_agent();
			
			$audit_data = array(
				'pay_id' => $pay_id,
				'user_id' => $user_id,
				'action' => $action,
				'field_changed' => $field_changed,
				'old_value' => $old_value,
				'new_value' => $new_value,
				'ip_address' => $ip_address,
				'user_agent' => substr($user_agent, 0, 500), // Truncate to fit column size
				'created_at' => date('Y-m-d H:i:s')
			);
			
			return $this->db->insert('payroll_audit_enhanced', $audit_data);
		}
	}
