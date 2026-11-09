<?php
	if(!defined('BASEPATH'))
		exit('No direct script access allowed');


	class Payroll_model extends CI_Model {

		function __construct() {

			parent::__construct();
		}

		/*store data*/

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

			for($i = 0; $i < count($employee_codes); $i++):

                $stringPos = strpos($employee_codes[$i], '_');
                $table = substr($employee_codes[$i], 0, $stringPos);
                $staff_code = substr($employee_codes[$i], $stringPos + 1);

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
				$data['petra'] = trim($this->input->post('petra'));
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
				$data['paid_by'] = $this->session->userdata('login_user_id');
				$data['employment_category'] = $table;
				$data['reference'] = $reference;

				if(!empty($payroll_id) || $payroll_id !== '') {

                	$this->db->where('employee_code', $staff_code);
                	$this->db->where('month', $payrollMonth);
                	$this->db->where('year', $payrollYear);
                	$query = $this->db->get('pay_salary');

                	if($query->num_rows() > 0) {
                		/*exists so we do update*/
                		$data['pay_id'] = $query->row()->pay_id;

                		$batchDataUpdate[] = $data;/*prepare for updates*/

                	} else { /*new staff was added to the list so we do insertion*/

                		$batchDataInsert[] = $data;
                	}


                } else { /*new insertion*/

                	$batchDataInsert[] = $data;
                }

                /*preparing sms*/
                $staffPhone = $this->db->get_where($table, [$table.'_code' => $staff_code])->row()->phone;

                $smsMessage = 'Your account has been credited with '.$currency.' '.number_format($data['net_salary'], 2, '.', ',').' as your Net Salary for '.$payrollMonth .' '.$payrollYear. '. Basic salary is '.$currency.' '.number_format($data['basic_salary'], 2, '.', ',').' and total deductions is '.$currency.' '.number_format($data['total_deductions'], 2, '.', ',').'. Payment reference: '.$reference.'. For more details, please login to your portal. Thank you.';

                $sms_data_array[$i]['To'] = $staffPhone;
				$sms_data_array[$i]['Content'] = $smsMessage;


			endfor;



			if(count($batchDataUpdate) > 0) {
				/*update*/
				$updateResult = $this->db->update_batch('pay_salary', $batchDataUpdate, 'pay_id');
			}

			if(count($batchDataInsert) > 0) {
				/*insert*/
				$insertResult = $this->db->insert_batch('pay_salary', $batchDataInsert);
			}


			if($updateResult && $insertResult) {

				$result = true;

			} else if($updateResult || $insertResult) {

				$result = true;

			} else {

				$result = false;
			}

			if($result == true) {
				/*send sms*/
				if($active_sms_service != 'disabled') {

					/*We can send sms*/
					$response = $this->sms_model->send_sms_batch_personalized($sms_data_array);
					
				}
			}

			return $result;
		}

		function getPayrollFormByStaffCode($staff_code, $month, $year) {

			$this->db->where('employee_code', $staff_code);
			$this->db->where('month', $month);
			$this->db->where('year', $year);
			$query = $this->db->get('pay_salary');

			return $query->result();

		}

		function getAllPayroll() {

			$query = $this->db->get('pay_salary');
			return $query->result_array();

		}

		function getStaffPayroll($staffCode) {

			$this->db->where('employee_code', $staffCode);
			$query = $this->db->get('pay_salary');
			return $query->result_array();

		}
	}