<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Expenditure model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Expenditure_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}

	function clear_cache() {
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
	}


	//Select all expenditure - generic
	function getAllexpenditures() {
		$this->db->order_by('expenditure_name', 'asc');
		$all_expenditure = $this->db->get('v_expenditure');

		return $all_expenditure;
	}

    //Select all expenditure list - generic
    function getAllexpenditureList() {
        $this->db->order_by('id', 'asc');
        $all_expenditure_list = $this->db->get('v_expenditure_list');

        return $all_expenditure_list;
    }

	//Select expenditure - by id
	function getExpenditureById($expenditure_id) {
		$this->db->where('expenditure_id', $expenditure_id);
        $expenditure_query = $this->db->get('v_expenditure');

		$registration_number = $expenditure_query->row()->registration_number;
		$type_id = $expenditure_query->row()->type_id;
		$model_id = $expenditure_query->row()->model_id;

		$expenditure = $registration_number. ' '.$this->getexpenditureTypeById($type_id).' '.$this->getexpenditureModelById($model_id);

		return $expenditure;
	}

    //Select expenditure - by id in array
    function getExpenditureByIdArray($expenditure_id) {
        $this->db->where('expenditure_id', $expenditure_id);
        $expenditure = $this->db->get('v_expenditure')->result();

        foreach($expenditure as $v) {
            return $v;
        }
    }


	//Add Expenditure
	function add_expenditure($expenditure_data) {

        //let's check if the account selected has enough fund for this transaction
        $account_query = $this->db->get_where('accounts', array('account_id' => $expenditure_data['account_id']))->row();
        $available_amount_in_account = $account_query->current_balance;

        //let's add the payment method here
        if($account_query->account_is_bank == 1) {
            $expenditure_data['payment_method'] = 'Bank';
        } else {
            $expenditure_data['payment_method'] = $account_query->name;
        }
        

        if($available_amount_in_account > $expenditure_data['expenditure_amount']) {
            //we have enough fund, go ahead
            $this->db->insert('v_expenditure', $expenditure_data);
            $expenditure_id = $this->db->insert_id();

            if($this->db->affected_rows() > 0) {
                //do the necessary deductions from the account...making sure the data is entered into the table before doing the math
                $this->db->where('account_id', $expenditure_data['account_id']);
                $this->db->set('current_balance', 'current_balance -'. $expenditure_data['expenditure_amount'], FALSE);
                $this->db->update('accounts'); //update account paid from

                $this->db->where('name', 'General Vehicle Expenses');
                $this->db->set('current_balance', 'current_balance + ' . $expenditure_data['expenditure_amount'], FALSE);
                $this->db->update('accounts'); //update allocated account

                //get total account balance for allocated account
                $allc_balance = $this->db->get_where('accounts', array('name' => 'General Vehicle Expenses'))->row()->current_balance;

                //get total account balance for Account paid from
                $acpf_balance = $this->db->get_where('accounts', array('account_id' =>  $expenditure_data['account_id']))->row()->current_balance;
                //Let's insert data into payment table
                //FOR PAYMENT TABLE
                $payment_data['payment_type'] = 'Expense';
                $payment_data['expenditure_item'] = $expenditure_data['expenditure_name'];
                $payment_data['amount'] = $expenditure_data['expenditure_amount'];
                $payment_data['due'] = 0;
                $payment_data['timestamp'] = $expenditure_data['expenditure_date'];
                $payment_data['day_timestamp'] = $expenditure_data['expenditure_date'];
                $payment_data['date'] = date('d-m-Y', $expenditure_data['expenditure_date']);
                $payment_data['account_id'] = $expenditure_data['account_id'];
                $payment_data['issuer_id']  = $this->session->userdata('login_user_id');

                //insert now
                $this->db->insert('payment', $payment_data);
                $payment_id = $this->db->insert_id();

                //FOR GENERAL JOURNAL ENTRY DEBIT THIS
                $journal_data_ap['payment_id'] = $payment_id;
                $journal_data_ap['timestamp'] = $payment_data['timestamp'];
                $journal_data_ap['date_timestamp'] = $payment_data['day_timestamp'];
                $journal_data_ap['date'] = $payment_data['date'];
                $journal_data_ap['expenditure_item'] = $payment_data['expenditure_item'];
                $journal_data_ap['journal'] = 'Payments';
                $journal_data_ap['account_balance'] = $allc_balance;
                $journal_data_ap['account_name'] = 'General Vehicle Expenses';
                $journal_data_ap['debit_amount'] = $expenditure_data['expenditure_amount'];
               // $journal_data_ap['reference'] = $this->input->post('reference_number');
                $journal_data_ap['description'] = 'EXPENDITURE MADE FOR '.$payment_data['expenditure_item'];
                $journal_data_ap['sales_person_id']  = $this->session->userdata('login_user_id');

                //INSERT NOW
                $this->db->insert('general_journal', $journal_data_ap);

                //FOR GENERAL JOURNAL ENTRY (ACCOUNT PAID FROM) CREDIT THIS
                $journal_data_apf['payment_id'] = $payment_id;
                $journal_data_apf['timestamp'] = $payment_data['timestamp'];
                $journal_data_apf['date_timestamp'] = $payment_data['day_timestamp'];
                $journal_data_apf['date'] = $payment_data['date'];
                $journal_data_apf['expenditure_item'] = $payment_data['expenditure_item'];
                $journal_data_apf['journal'] = 'Payments';
                $journal_data_apf['account_balance'] = $acpf_balance;
                $journal_data_apf['account_name'] = $this->db->get_where('accounts', array('account_id' => $expenditure_data['account_id']))->row()->name;
                $journal_data_apf['credit_amount'] = $expenditure_data['expenditure_amount'];
                //$journal_data_apf['reference'] = $this->input->post('reference_number');
                $journal_data_apf['description'] = 'EXPENDITURE MADE FOR '.$payment_data['expenditure_item'];
                $journal_data_apf['sales_person_id']  = $this->session->userdata('login_user_id');

                //INSERT NOW
                $this->db->insert('general_journal', $journal_data_apf);

                $journal_id = $this->db->insert_id();

                //update payment table with this id
                $this->db->where('payment_id', $payment_id);
                $this->db->set('journal_id', $journal_id);
                $this->db->set('v_expenditure_id', $expenditure_id);
                $this->db->update('payment');

                return $expenditure_id; //successful
            } else {
                return 0; //failed
            }

        } else {
            //insufficient balance so reject it
            return 'insufficient balance';

        }
	}


    //Edit Expenditure
    function edit_expenditure($expenditure_data, $expenditure_id) {

        //let's check if the account selected has enough fund for this transaction
        $account_query = $this->db->get_where('accounts', array('account_id' => $expenditure_data['account_id']))->row();
        $available_amount_in_account = $account_query->current_balance;

        $expenditure_info = $this->expenditure_model->getExpenditureByIdArray($expenditure_id);
        $prev_amount = $expenditure_info->expenditure_amount;
        $prev_account = $expenditure_info->account_id;
        $payment_query = $this->db->get_where('payment', array('v_expenditure_id' => $expenditure_id))->row();
        $payment_id = $payment_query->payment_id;
        $journal_id = $payment_query->journal_id;

        $diff = $expenditure_data['expenditure_amount'] - $prev_amount;

        //get the previous amount stated for this expenditure

        //let's add the payment method here
        if($account_query->account_is_bank == 1) {
            $expenditure_data['payment_method'] = 'Bank';
        } else {
            $expenditure_data['payment_method'] = $account_query->name;
        }


        //compare the accounts, if the user selected the same account, we can check if there's enough fund by comparing with the "$diff" else we use the current amount stated
        if($prev_account == $expenditure_data['account_id']) {
            //same account
            if($available_amount_in_account > $diff) {
                //we have enough fund, go ahead
                $this->db->where('expenditure_id', $expenditure_id);
                $this->db->update('v_expenditure', $expenditure_data);


                //if($this->db->affected_rows() > 0) {
                    //do the necessary deductions from the account...making sure the data is entered into the table before doing the math
                    $this->db->where('account_id', $expenditure_data['account_id']);
                    $this->db->set('current_balance', 'current_balance -'. $diff, FALSE);
                    $this->db->update('accounts'); //update account paid from

                    $this->db->where('name', 'General Vehicle Expenses');
                    $this->db->set('current_balance', 'current_balance + ' . $diff, FALSE);
                    $this->db->update('accounts'); //update allocated account

                    //get total account balance for allocated account
                    $allc_balance = $this->db->get_where('accounts', array('name' => 'General Vehicle Expenses'))->row()->current_balance;

                    //get total account balance for Account paid from
                    $acpf_balance = $this->db->get_where('accounts', array('account_id' =>  $expenditure_data['account_id']))->row()->current_balance;
                    //Let's insert data into payment table
                    //FOR PAYMENT TABLE
                    $payment_data['payment_type'] = 'Expense';
                    $payment_data['expenditure_item'] = $expenditure_data['expenditure_name'];
                    $payment_data['amount'] = $expenditure_data['expenditure_amount'];
                    $payment_data['due'] = 0;
                    $payment_data['timestamp'] = $expenditure_data['expenditure_date'];
                    $payment_data['day_timestamp'] = $expenditure_data['expenditure_date'];
                    $payment_data['date'] = date('d-m-Y', $expenditure_data['expenditure_date']);
                    $payment_data['account_id'] = $expenditure_data['account_id'];
                    $payment_data['issuer_id']  = $this->session->userdata('login_user_id');

                    //update now
                    $this->db->where('v_expenditure_id', $expenditure_id);
                    $this->db->update('payment', $payment_data);

                    //FOR GENERAL JOURNAL ENTRY DEBIT THIS
                    $journal_data_ap['payment_id'] = $payment_id;
                    $journal_data_ap['timestamp'] = $payment_data['timestamp'];
                    $journal_data_ap['date_timestamp'] = $payment_data['day_timestamp'];
                    $journal_data_ap['date'] = $payment_data['date'];
                    $journal_data_ap['expenditure_item'] = $payment_data['expenditure_item'];
                    $journal_data_ap['journal'] = 'Payments';
                    $journal_data_ap['account_balance'] = $allc_balance;
                    $journal_data_ap['account_name'] = 'General Vehicle Expenses';
                    $journal_data_ap['debit_amount'] = $expenditure_data['expenditure_amount'];
                   // $journal_data_ap['reference'] = $this->input->post('reference_number');
                    $journal_data_ap['description'] = 'EXPENDITURE MADE FOR '.$payment_data['expenditure_item'];
                    $journal_data_ap['sales_person_id']  = $this->session->userdata('login_user_id');

                    //UPDATE NOW
                    $this->db->where('payment_id', $payment_id);
                    $this->db->where('account_name', 'General Vehicle Expenses');
                    $this->db->update('general_journal', $journal_data_ap);

                    //FOR GENERAL JOURNAL ENTRY (ACCOUNT PAID FROM) CREDIT THIS
                    $journal_data_apf['payment_id'] = $payment_id;
                    $journal_data_apf['timestamp'] = $payment_data['timestamp'];
                    $journal_data_apf['date_timestamp'] = $payment_data['day_timestamp'];
                    $journal_data_apf['date'] = $payment_data['date'];
                    $journal_data_apf['expenditure_item'] = $payment_data['expenditure_item'];
                    $journal_data_apf['journal'] = 'Payments';
                    $journal_data_apf['account_balance'] = $acpf_balance;
                    $journal_data_apf['account_name'] = $this->db->get_where('accounts', array('account_id' => $expenditure_data['account_id']))->row()->name;
                    $journal_data_apf['credit_amount'] = $expenditure_data['expenditure_amount'];
                    //$journal_data_apf['reference'] = $this->input->post('reference_number');
                    $journal_data_apf['description'] = 'EXPENDITURE MADE FOR '.$payment_data['expenditure_item'];
                    $journal_data_apf['sales_person_id']  = $this->session->userdata('login_user_id');

                    //UPDATE NOW
                    $this->db->where('payment_id', $payment_id);
                    $this->db->where('journal_id', $journal_id);
                    $this->db->update('general_journal', $journal_data_apf);

                    return $expenditure_id; //successful
                    
                /*} else {
                    return 0; //failed
                }*/

            } else {
                //insufficient balance so reject it
                return 'insufficient balance';

            }


        } else {
            //different account
            if($available_amount_in_account > $expenditure_data['expenditure_amount']) {
                //we have enough fund, go ahead
                $this->db->where('expenditure_id', $expenditure_id);
                $this->db->update('v_expenditure', $expenditure_data);

                //if($this->db->affected_rows() > 0) {
                    //do the necessary deductions from the account...making sure the data is entered into the table before doing the math
                    

                    //a different account is chosen. Refund the amount paid from that account back before proceeding with the new entry
                    //update previous account paid from - add to it
                    $this->db->where('account_id', $prev_account);
                    $this->db->set('current_balance', 'current_balance + ' . $prev_amount, FALSE);
                    $this->db->update('accounts');

                    //update current account paid from - reduce it
                    $this->db->where('account_id', $expenditure_data['account_id']);
                    $this->db->set('current_balance', 'current_balance -'. $expenditure_data['expenditure_amount'], FALSE);
                    $this->db->update('accounts'); //update account paid from



                    $this->db->where('name', 'General Vehicle Expenses');
                    $this->db->set('current_balance', 'current_balance + ' . $expenditure_data['expenditure_amount'], FALSE);
                    $this->db->update('accounts'); //update allocated account

                    //get total account balance for allocated account
                    $allc_balance = $this->db->get_where('accounts', array('name' => 'General Vehicle Expenses'))->row()->current_balance;

                    //get total account balance for Account paid from
                    $acpf_balance = $this->db->get_where('accounts', array('account_id' =>  $expenditure_data['account_id']))->row()->current_balance;
                    //Let's insert data into payment table
                    //FOR PAYMENT TABLE
                    $payment_data['payment_type'] = 'Expense';
                    $payment_data['expenditure_item'] = $expenditure_data['expenditure_name'];
                    $payment_data['amount'] = $expenditure_data['expenditure_amount'];
                    $payment_data['due'] = 0;
                    $payment_data['timestamp'] = $expenditure_data['expenditure_date'];
                    $payment_data['day_timestamp'] = $expenditure_data['expenditure_date'];
                    $payment_data['date'] = date('d-m-Y', $expenditure_data['expenditure_date']);
                    $payment_data['account_id'] = $expenditure_data['account_id'];
                    $payment_data['issuer_id']  = $this->session->userdata('login_user_id');

                    //update now
                    $this->db->where('v_expenditure_id', $expenditure_id);
                    $this->db->update('payment', $payment_data);

                    //FOR GENERAL JOURNAL ENTRY DEBIT THIS
                    $journal_data_ap['payment_id'] = $payment_id;
                    $journal_data_ap['timestamp'] = $payment_data['timestamp'];
                    $journal_data_ap['date_timestamp'] = $payment_data['day_timestamp'];
                    $journal_data_ap['date'] = $payment_data['date'];
                    $journal_data_ap['expenditure_item'] = $payment_data['expenditure_item'];
                    $journal_data_ap['journal'] = 'Payments';
                    $journal_data_ap['account_balance'] = $allc_balance;
                    $journal_data_ap['account_name'] = 'General Vehicle Expenses';
                    $journal_data_ap['debit_amount'] = $expenditure_data['expenditure_amount'];
                   // $journal_data_ap['reference'] = $this->input->post('reference_number');
                    $journal_data_ap['description'] = 'EXPENDITURE MADE FOR '.$payment_data['expenditure_item'];
                    $journal_data_ap['sales_person_id']  = $this->session->userdata('login_user_id');

                    //UPDATE NOW
                    $this->db->where('payment_id', $payment_id);
                    $this->db->where('account_name', 'General Vehicle Expenses');
                    $this->db->update('general_journal', $journal_data_ap);

                    //FOR GENERAL JOURNAL ENTRY (ACCOUNT PAID FROM) CREDIT THIS
                    $journal_data_apf['payment_id'] = $payment_id;
                    $journal_data_apf['timestamp'] = $payment_data['timestamp'];
                    $journal_data_apf['date_timestamp'] = $payment_data['day_timestamp'];
                    $journal_data_apf['date'] = $payment_data['date'];
                    $journal_data_apf['expenditure_item'] = $payment_data['expenditure_item'];
                    $journal_data_apf['journal'] = 'Payments';
                    $journal_data_apf['account_balance'] = $acpf_balance;
                    $journal_data_apf['account_name'] = $this->db->get_where('accounts', array('account_id' => $expenditure_data['account_id']))->row()->name;
                    $journal_data_apf['credit_amount'] = $expenditure_data['expenditure_amount'];
                    //$journal_data_apf['reference'] = $this->input->post('reference_number');
                    $journal_data_apf['description'] = 'EXPENDITURE MADE FOR '.$payment_data['expenditure_item'];
                    $journal_data_apf['sales_person_id']  = $this->session->userdata('login_user_id');

                    //UPDATE NOW
                    $this->db->where('payment_id', $payment_id);
                    $this->db->where('journal_id', $journal_id);
                    $this->db->update('general_journal', $journal_data_apf);

                    return $expenditure_id; //successful

                /*} else {
                    return 0; //failed
                }*/

            } else {
                //insufficient balance so reject it
                return 'insufficient balance';

            }
        }
    }

    //Delete Expenditure
    function delete_expenditure($expenditure_id) {
        $this->clear_cache();
        //check if it is in the loading table
        $can_delete = true;

        //payment table query
        $payment_query = $this->db->get_where('payment', array('v_expenditure_id' => $expenditure_id))->row();
        $account_id = $payment_query->account_id;
        $amount = $payment_query->amount;

        //update account balance and general vehicle expenses
        $this->db->where('account_id', $account_id);
        $this->db->set('current_balance', 'current_balance + '.$amount, FALSE);
        $this->db->update('accounts');//add back to account paid from

        $this->db->where('name', 'General Vehicle Expenses');
        $this->db->set('current_balance', 'current_balance - '.$amount, FALSE);
        $this->db->update('accounts'); //deduct from the General Vehicle Expenses

        //work in the general journal
        $journal_id = $payment_query->journal_id;
        $journal_id2 = $journal_id - 1;

        $this->db->where('journal_id', $journal_id);
        $this->db->delete('general_journal');

        $this->db->where('journal_id', $journal_id2);
        $this->db->delete('general_journal');

        //work in the payment table
        $payment_id = $payment_query->payment_id;
        $this->db->where('payment_id', $payment_id);
        $this->db->delete('payment');

        //working in the expenditure table
        $this->db->where('expenditure_id', $expenditure_id);
        $this->db->delete('v_expenditure');
        
        return $can_delete;
        
    }

  //FILTER expenditure LIST
  function getAllExpendituresDataTable($from = '', $to = '', $type = '', $vehicle = '', $driver = '') {

		$expenditure_array = array();
		$data = array();
        $total = 0;

        //currency
        $currency = get_settings('currency');
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);


        $columns = array(
            0 => 'expenditure_id',
            1 => 'sn',
            2 => 'name',
            3 => 'amount',
            4 => 'vehicle',
            5 => 'driver',
            6 => 'date_created',
            7 => 'date_modified',
            8 => 'date',
            9 => 'type',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allExpenditureCount($from, $to, $type, $vehicle, $driver);
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $expenditure_query = $this->ajaxload->getAllExpendituresDataTable($limit,$start,$order,$dir, $from, $to, $type, $vehicle, $driver);
        }
        else {
            $search = $this->input->post('search')['value'];
            $expenditure_query =  $this->ajaxload->expenditureSearch($limit,$start,$search,$order,$dir, $from, $to, $type, $vehicle, $driver);
            $totalFiltered = $this->ajaxload->expenditureSearchCount($search, $from, $to, $type, $vehicle, $driver);
        }
        

        if(!empty($expenditure_query)) {
        		$sn = 1;
            foreach ($expenditure_query as $expenditure) {
                

                $nestedData['sn'] = $sn;
                $nestedData['date'] = date('d-m-Y', $expenditure->expenditure_date);
                $nestedData['name'] = $expenditure->expenditure_name;
                $nestedData['type'] = strtoupper($expenditure->expenditure_type);
                $nestedData['vehicle'] = $this->vehicle_model->getVehicleById($expenditure->vehicle_id);
                $nestedData['vehicle_reg'] = $this->vehicle_model->getVehicleRegistrationNumberById($expenditure->vehicle_id);
                $nestedData['driver'] = $this->driver_model->getDriverNameById($expenditure->driver_id);
                $nestedData['amount'] = numfmt_format_currency($fmt, $expenditure->expenditure_amount, $currency);
                $nestedData['date_created'] = date('d-m-Y', $expenditure->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $expenditure->date_modified);

                $nestedData['DT_RowId'] = 'expenditure_id_'.$expenditure->expenditure_id;
                $nestedData['onclick'] = "populate_links('".$expenditure->expenditure_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $expenditure_array[] = $expenditure->expenditure_id;

							$sn++;  
                            $total += $expenditure->expenditure_amount;          
            }
				    $bt_row['bottom_row'] = '
										            <div class="col-md-6 col-sm-6 text-lg-left"><strong>TOTAL EXPENDITURE: ' .numfmt_format_currency($fmt, $total, $currency).'</strong></div>';

        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "expenditure_ids"     => $expenditure_array,
            "bt_row"     		  => $bt_row,
            "total_expenditure"   => numfmt_format_currency($fmt, $total, $currency)
        );

        echo json_encode($json_data);
    }

}