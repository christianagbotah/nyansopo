<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Financial_report_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    function clear_cache() {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }


    /*
    *====================================================================
       ================= START OF ACCOUNTS RECEIVABLES===================
    *====================================================================
    */
    /*Account Receivables Report Starts*/
    function accountsReceivables($term, $year, $type, $category, $class_id, $student, $all_customize, $residence_type=0) {

        //Type selection
        if($type == 1) {
            //Billed Invoice selected

            //Category selection
            if($category == 0) { //=============================
                //All students selected
                if($all_customize == 0) { //all dates

                    $allStudents = $this->financial_report_model->getAllBilledStudentsOwingByDate([], $residence_type);

                } else { //customized

                    $allStudents = $this->financial_report_model->getCustomizedBilledStudentsOwingByDate($term, $year, [], $residence_type);
                }
                
                return $allStudents;

            } else if($category == 1) {
                //filter by class

                /*Either all students in the selected class or 
                *just a aparticular student(s)
                */
                if($student == 0) {
                    //all student in the selected class
                    $students_ids = [];
                    $allStudentsData = $this->crud_model->get_students($class_id);

                    if(count($allStudentsData) > 0) { 

                        foreach($allStudentsData as $st) {
                            array_push($students_ids, $st['student_id']);
                        }

                        //All students selected
                        if($all_customize == 0) { //all dates

                            $allStudents = $this->financial_report_model->getAllBilledStudentsOwingByDate($students_ids, $residence_type);

                        } else { //customized

                            $allStudents = $this->financial_report_model->getCustomizedBilledStudentsOwingByDate($term, $year, $students_ids, $residence_type);
                        }

                    } else {
                        $allStudents = [];
                    }
                    
                    return $allStudents;
                } else {
                    //specific student(s) selected
                    $students_ids = [];
                    array_push($students_ids, $student);

                    //All students selected
                    if($all_customize == 0) { //all dates

                        $allStudents = $this->financial_report_model->getAllBilledStudentsOwingByDate($students_ids, $residence_type);

                    } else { //customized

                        $allStudents = $this->financial_report_model->getCustomizedBilledStudentsOwingByDate($start_date, $end_date, $students_ids, $residence_type);
                    }

                    
                    return $allStudents;
                }

            }

        } else {
            //Classes, Feeding & Transport selected

            //Category selection
            if($category == 0) { //=======================
                //All students selected
                $fctOwings = $this->financial_report_model->getAllStudentsOwingAllfct();
                return $fctOwings;

            } else if($category == 1) {
                //filter by class

                /*Either all students in the selected class or 
                *just a aparticular student(s)
                */
                if($student == 0) {
                    //all student in the selected class
                    $students_ids = [];
                    $allStudentsData = $this->crud_model->get_students($class_id);

                    if(count($allStudentsData) > 0) { 

                        foreach($allStudentsData as $st) {
                            array_push($students_ids, $st['student_id']);
                        }
                        $allStudents = $this->financial_report_model->getAllStudentsOwingAllfctById($students_ids);
                    } else {
                        $allStudents = [];
                    }
                    
                    return $allStudents;
                } else {
                    //specific student(s) selected
                    $students_ids = [];
                    array_push($students_ids, $student);
                    $allStudents = $this->financial_report_model->getAllStudentsOwingAllfctById($students_ids);
                    
                    return $allStudents;
                }

            }
        }
    } /*End of Accounts Receivables Reports*/


    /*
    ====================================================================
    =========================== GET FUNCTIONS ==========================
    ====================================================================
    */

    //get all students owing all fct
    function getAllStudentsOwingAllfct() {
        $this->db->select('w.student_id');
        $this->db->from('daily_fee_wallet w');
        $this->db->join('student s', 's.student_id = w.student_id');
        $this->db->where('s.mute', '0');
        $this->db->where('(w.feeding_arrears > 0 OR w.breakfast_arrears > 0 OR w.classes_arrears > 0 OR w.water_arrears > 0 OR w.transport_arrears > 0)');
        $this->db->group_by('w.student_id');
        
        return $this->db->get()->result_array();
    }

    //get all students owing all fct by class_id or student_id
    function getAllStudentsOwingAllfctById($student_ids = array()) {
        $this->db->select('w.student_id');
        $this->db->from('daily_fee_wallet w');
        $this->db->join('student s', 's.student_id = w.student_id');
        $this->db->where('s.mute', '0');
        $this->db->where('(w.feeding_arrears > 0 OR w.breakfast_arrears > 0 OR w.classes_arrears > 0 OR w.water_arrears > 0 OR w.transport_arrears > 0)');
        
        if(count($student_ids) > 0) {
            $this->db->where_in('w.student_id', $student_ids);
        }
        
        $this->db->group_by('w.student_id');
        return $this->db->get()->result_array();
    }

    //get only billed students owing
    function getAllBilledStudentsOwingByDate($student_ids = array(), $residence_type=0) {

        if(count($student_ids) > 0) {
            //class selected
            //select all students from the invoice table first
            $this->db->select('student_id');
            $this->db->where('can_delete !=', 'trash');
            $this->db->from('invoice');
            $this->db->distinct();

            if($residence_type != '0') {

                $this->db->where('residence_type', $residence_type);

            }

            if($residence_type == '0') {

                $this->db->order_by('residence_type', 'asc');
            }

            $this->db->where('mute', '0'); //only active students
            $this->db->where_in('student_id', $student_ids);
            $this->db->where('student_id IS NOT NULL');
            $this->db->where('due >', '0');

        } else {
            
            //select all students from the invoice table first
            $this->db->select('student_id');
            $this->db->from('invoice');
            $this->db->where('can_delete !=', 'trash');
            $this->db->distinct();

            if($residence_type != '0') {

                $this->db->where('residence_type', $residence_type);

            }

            if($residence_type == '0') {

                $this->db->order_by('residence_type', 'asc');
            }

            $this->db->where('mute', '0'); //only active students
            $this->db->where('student_id IS NOT NULL');
            $this->db->where('due >', '0');
        }
        
        $billed_students = $this->db->get()->result_array(); 
        
        return $billed_students;
    } //end of billed students

    //get only billed students owing
    function getCustomizedBilledStudentsOwingByDate($term, $year, $student_ids = array(), $residence_type=0) {

        if(count($student_ids) > 0) {
            //class selected
            //select all students from the invoice table first
            $this->db->select('student_id');
            $this->db->from('invoice');
            $this->db->where('can_delete !=', 'trash');
            $this->db->distinct();

            if($residence_type != '0') {

                $this->db->where('residence_type', $residence_type);

            }

            if($residence_type == '0') {

                $this->db->order_by('residence_type', 'asc');
            }

            $this->db->where('mute', '0'); //only active students
            $this->db->where_in('student_id', $student_ids);
            $this->db->where('due >', '0');
            /*$this->db->where('term', $term);
            $this->db->where('year', $year);*/

        } else {
            
            //select all students from the invoice table first
            $this->db->select('student_id');
            $this->db->from('invoice');
            $this->db->where('can_delete !=', 'trash');
            $this->db->distinct();

            if($residence_type != '0') {

                $this->db->where('residence_type', $residence_type);

            }

            if($residence_type == '0') {

                $this->db->order_by('residence_type', 'asc');
            }

            $this->db->where('mute', '0'); //only active students
            $this->db->where('due >', '0');
            /*$this->db->where('term', $term);
            $this->db->where('year', $year);*/
        }
        
        $billed_students = $this->db->get()->result_array(); 
        
        return $billed_students;
    } //end of billed students

    //get only fedding fees, classes fees or transport fare
    function getAllStudentsOwingfct($fees = '', $student_ids = array()) {
        $this->db->select('w.student_id');
        $this->db->from('daily_fee_wallet w');
        $this->db->join('student s', 's.student_id = w.student_id');
        $this->db->where('s.mute', '0');
        
        if(count($student_ids) > 0) {
            $this->db->where_in('w.student_id', $student_ids);
        }
        
        if($fees == 'feeding') {
            $this->db->where('w.feeding_arrears >', 0);
        } else if($fees == 'classes') {
            $this->db->where('w.classes_arrears >', 0);
        } else if($fees == 'transport') {
            $this->db->where('w.transport_arrears >', 0);
        } else {
            return false;
        }
        
        $result = $this->db->get()->result_array();
        return $result;
    } //end of feeding, classes and transport


    //get bill invoice codes owing by student id
    function getAllBillInvoicesOwingByStudentId($student_id) {

        $this->db->select('invoice_code');
        $this->db->distinct();
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('student_id', $student_id);
        $this->db->where('due >', '0');
        $invoice_code = $this->db->get('invoice')->result_array(); 
        
        return $invoice_code;
    }

    function getAllBillInvoicesIdsOwingByStudentId($student_id) {

        $this->db->select('invoice_id');
        $this->db->distinct();
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('student_id', $student_id);
        $this->db->where('due >', '0');
        $invoice_code = $this->db->get('invoice')->result_array(); 
        
        return $invoice_code;
    }

    function getCustomizedBillInvoicesOwingByStudentId($term, $year, $student_id) {

        $this->db->select('invoice_code');
        $this->db->distinct();
        $this->db->where('student_id', $student_id);
        $this->db->where('due >', '0');
        $this->db->where('term', $term);
        $this->db->where('year', $year);
        $this->db->where('can_delete !=', 'trash');
        $invoice_code = $this->db->get('invoice')->result_array(); 
        
        return $invoice_code;
    }


    function getArrearsByStudentId($year, $student_id) {

        $this->db->where('student_id', $student_id);
        $this->db->where('due >', '0');
        $this->db->where('year <=', $year);
        $this->db->where('can_delete !=', 'trash');
        $arrears_array = $this->db->get('invoice')->result_array();

        return $arrears_array;
    }

    function getAmountPaidByStudentId($term, $year, $student_id) {

        $this->db->select_sum('amount');
        $this->db->where('student_id', $student_id);
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $amount_paid = $this->db->get('payment')->row()->amount;

        return $amount_paid;
    }

    function getThisTermBillByStudentId($term, $year, $student_id) {

        $this->db->select_sum('amount');
        $this->db->where('student_id', $student_id);
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $this->db->where('can_delete !=', 'trash');
        $bill_amount = $this->db->get('invoice')->row()->amount;

        return $bill_amount;
    }

    

    //get bill owing by invoice code
    function getBillOwingByInvoiceCode($invoice_code) {

        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('due >', '0');
        $this->db->where('can_delete !=', 'trash');
        $bills = $this->db->get('invoice')->result_array(); 
        
        return $bills;
    }

    function getBillOweSumByInvoiceCode($invoice_code) {

        $this->db->select_sum('due');
        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('due >', '0');
        $this->db->where('can_delete !=', 'trash');
        $billOwe = $this->db->get('invoice')->row()->due; 
        
        return $billOwe;
    }

    //get bill owing by invoice code row
    function getBillOwingRowByInvoiceCode($invoice_code) {

        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('due >', '0');
        $this->db->where('can_delete !=', 'trash');
        $bills = $this->db->get('invoice')->row(); 
        
        return $bills;
    }

    //get feeding, classes and transport fare by student id
    function getfctOwingByStudentId($student_id, $fees = '', $last_timestamp = '') {
        $wallet = $this->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row();
        
        if(!$wallet) return 0;
        
        if($fees == 'feeding') {
            $result = $wallet->feeding_arrears;
        } else if($fees == 'classes') {
            $result = $wallet->classes_arrears;
        } else if($fees == 'transport') {
            $result = $wallet->transport_arrears;
        } else if($fees == 'breakfast') {
            $result = $wallet->breakfast_arrears;
        } else if($fees == 'water') {
            $result = $wallet->water_arrears;
        } else {
            $result = 0;
        }
        
        return $result > 0 ? $result : 0;
    } //end of feeding, classes and transport for each student

    /*
    *====================================================================
       ================= END OF ACCOUNTS RECEIVABLES===================
    *====================================================================
    */


    /*
    *====================================================================
       ================= START OF ACCOUNTS PAYABLES===================
    *====================================================================
    */
    /*Account Payables Report Starts*/
    function accountsPayables($start_date, $end_date, $type, $category, $class_id, $student) {

        //Type selection
        if($type == 1) {
            //Billed Invoice selected

            //Category selection
            if($category == 0) { //=============================
                //All students selected
                $allStudents = $this->financial_report_model->getAllBilledStudentsPayableByDate($start_date, $end_date);
                return $allStudents;

            } else if($category == 1) {
                //filter by class

                /*Either all students in the selected class or 
                *just a aparticular student(s)
                */
                if($student == 0) {
                    //all student in the selected class
                    $students_ids = [];
                    $allStudentsData = $this->crud_model->get_students($class_id);

                    if(count($allStudentsData) > 0) { 

                        foreach($allStudentsData as $st) {
                            array_push($students_ids, $st['student_id']);
                        }
                        $allStudents = $this->financial_report_model->getAllBilledStudentsPayableByDate($start_date, $end_date, $students_ids);
                    } else {
                        $allStudents = [];
                    }
                    
                    return $allStudents;
                } else {
                    //specific student(s) selected
                    $students_ids = [];
                    array_push($students_ids, $student);
                    $allStudents = $this->financial_report_model->getAllBilledStudentsPayableByDate($start_date, $end_date, $students_ids);
                    
                    return $allStudents;
                }

            }

        } else {
            //Classes, Feeding & Transport selected

            //Category selection
            if($category == 0) { //=======================
                //All students selected
                $fctOwings = $this->financial_report_model->getAllStudentsPayableAllfct();
                return $fctOwings;

            } else if($category == 1) {
                //filter by class

                /*Either all students in the selected class or 
                *just a aparticular student(s)
                */
                if($student == 0) {
                    //all student in the selected class
                    $students_ids = [];
                    $allStudentsData = $this->crud_model->get_students($class_id);

                    if(count($allStudentsData) > 0) { 

                        foreach($allStudentsData as $st) {
                            array_push($students_ids, $st['student_id']);
                        }
                        $allStudents = $this->financial_report_model->getAllStudentsPayableAllfctById($students_ids);
                    } else {
                        $allStudents = [];
                    }

                    
                    return $allStudents;
                } else {
                    //specific student(s) selected
                    $students_ids = [];
                    array_push($students_ids, $student);
                    $allStudents = $this->financial_report_model->getAllStudentsPayableAllfctById($students_ids);
                    
                    return $allStudents;
                }

            }
        }
    } /*End of Accounts Receivables Reports*/


    /*
    ====================================================================
    =========================== GET FUNCTIONS ==========================
    ====================================================================
    */

    //get all students owing all fct
    function getAllStudentsPayableAllfct() {
        $this->db->select('w.student_id');
        $this->db->from('daily_fee_wallet w');
        $this->db->join('student s', 's.student_id = w.student_id');
        $this->db->where('s.mute', '0');
        $this->db->where('(w.feeding_arrears < 0 OR w.breakfast_arrears < 0 OR w.classes_arrears < 0 OR w.water_arrears < 0 OR w.transport_arrears < 0)');
        $this->db->group_by('w.student_id');
        
        return $this->db->get()->result_array();
    }

    //get all students owing all fct by class_id or student_id
    function getAllStudentsPayableAllfctById($student_ids = array()) {
        $this->db->select('w.student_id');
        $this->db->from('daily_fee_wallet w');
        $this->db->join('student s', 's.student_id = w.student_id');
        $this->db->where('s.mute', '0');
        $this->db->where('(w.feeding_arrears < 0 OR w.breakfast_arrears < 0 OR w.classes_arrears < 0 OR w.water_arrears < 0 OR w.transport_arrears < 0)');
        
        if(count($student_ids) > 0) {
            $this->db->where_in('w.student_id', $student_ids);
        }
        
        $this->db->group_by('w.student_id');
        return $this->db->get()->result_array();
    }

    //get only billed students owing
    function getAllBilledStudentsPayableByDate($start_date, $end_date, $student_ids = array()) {

        if(count($student_ids) > 0) {
            //class selected
            //select all students from the invoice table first
            $this->db->select('student_id');
            $this->db->from('invoice');
            $this->db->where('can_delete !=', 'trash');
            $this->db->distinct();
            $this->db->where('mute', '0'); //only active students
            $this->db->where_in('student_id', $student_ids);
            $this->db->where('due <', '0');
            $this->db->where('creation_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');

        } else {
            //select all students from the invoice table first
            $this->db->select('student_id');
            $this->db->from('invoice');
            $this->db->distinct();
            $this->db->where('can_delete !=', 'trash');
            $this->db->where('mute', '0'); //only active students
            $this->db->where('due <', '0');
            $this->db->where('creation_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        }
        
        $billed_students = $this->db->get()->result_array(); 
        
        return $billed_students;
    } //end of billed students

    //get only fedding fees, classes fees or transport fare
    function getAllStudentsPayablefct($fees = '', $student_ids = array()) {
        $this->db->select('w.student_id');
        $this->db->from('daily_fee_wallet w');
        $this->db->join('student s', 's.student_id = w.student_id');
        $this->db->where('s.mute', '0');
        
        if(count($student_ids) > 0) {
            $this->db->where_in('w.student_id', $student_ids);
        }
        
        if($fees == 'feeding') {
            $this->db->where('w.feeding_arrears <', 0);
        } else if($fees == 'classes') {
            $this->db->where('w.classes_arrears <', 0);
        } else if($fees == 'transport') {
            $this->db->where('w.transport_arrears <', 0);
        } else {
            return false;
        }
        
        $result = $this->db->get()->result_array();
        return $result;
    } //end of feeding, classes and transport


    //get bill invoice codes owing by student id
    function getBillInvoicesPayableByStudentId($start_date, $end_date, $student_id) {

        $this->db->select('invoice_code');
        $this->db->distinct();
        $this->db->where('student_id', $student_id);
        $this->db->where('due <', '0');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('creation_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $invoice_code = $this->db->get('invoice')->result_array(); 
        
        return $invoice_code;
    }

    //get bill owing by invoice code
    function getBillPayableByInvoiceCode($invoice_code) {

        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('due <', '0');
        $this->db->where('can_delete !=', 'trash');
        $bills = $this->db->get('invoice')->result_array(); 
        
        return $bills;
    }

    //get bill owing by invoice code row
    function getBillPayableRowByInvoiceCode($invoice_code) {

        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('due <', '0');
        $this->db->where('can_delete !=', 'trash');
        $bills = $this->db->get('invoice')->row(); 
        
        return $bills;
    }

    //get feeding, classes and transport fare by student id
    function getfctPayableByStudentId($student_id, $fees = '', $last_timestamp = '') {
        $wallet = $this->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row();
        
        if(!$wallet) return 0;
        
        if($fees == 'feeding') {
            $result = $wallet->feeding_arrears;
        } else if($fees == 'classes') {
            $result = $wallet->classes_arrears;
        } else if($fees == 'transport') {
            $result = $wallet->transport_arrears;
        } else if($fees == 'breakfast') {
            $result = $wallet->breakfast_arrears;
        } else if($fees == 'water') {
            $result = $wallet->water_arrears;
        } else {
            $result = 0;
        }
        
        return $result < 0 ? $result : 0;
    } //end of feeding, classes and transport for each student

    /*
    *====================================================================
       ================= END OF ACCOUNTS PAYABLES===================
    *====================================================================
    */



    /*
    *====================================================================
       ================= START OF STUDENTS PAYMENTS REPORT===================
    *====================================================================
    */
    /*Students payments Report Starts*/
    function paymentsReport($start_date, $end_date, $type, $category, $class_id, $student, $residence_type=0, $bill_item='0', $payment_method='0') {

        //Type selection
        if($type == 1) {
            //Billed Invoice selected

            //Category selection
            if($category == 0) { //=============================
                //All students selected
                $allStudents = $this->financial_report_model->getAllBilledStudentsPaymentByDate($start_date, $end_date, [], $residence_type, $bill_item, $payment_method);
                return $allStudents;

            } else if($category == 1) {
                //filter by class

                /*Either all students in the selected class or 
                *just a aparticular student(s)
                */
                if($student == 0) {
                    //all student in the selected class
                    $students_ids = [];
                    $allStudentsData = $this->crud_model->get_students($class_id);

                    if(count($allStudentsData) > 0) {
                        foreach($allStudentsData as $st) {
                            array_push($students_ids, $st['student_id']);
                        }
                        $allStudents = $this->financial_report_model->getAllBilledStudentsPaymentByDate($start_date, $end_date, $students_ids, $residence_type, $bill_item, $payment_method);
                    } else {
                        $allStudents = [];
                    }
                    
                    return $allStudents;
                } else {
                    //specific student(s) selected
                    $students_ids = [];
                    array_push($students_ids, $student);
                    $allStudents = $this->financial_report_model->getAllBilledStudentsPaymentByDate($start_date, $end_date, $students_ids, $residence_type, $bill_item, $payment_method);
                    
                    return $allStudents;
                }

            }

        } else {
            //Classes, Feeding & Transport selected

            //Category selection
            if($category == 0) { //=======================
                //All students selected
                $students_ids = [];
                $fctPayments = $this->financial_report_model->getAllStudentsPaymentfct($students_ids, $start_date, $end_date, $residence_type, $payment_method);
                return $fctPayments;

            } else if($category == 1) {
                //filter by class

                /*Either all students in the selected class or 
                *just a aparticular student(s)
                */
                if($student == 0) {
                    //all student in the selected class
                    $students_ids = [];
                    $allStudentsData = $this->crud_model->get_students($class_id);

                    if(count($allStudentsData) > 0) { 

                        foreach($allStudentsData as $st) {
                            array_push($students_ids, $st['student_id']);
                        }
                        $allStudents = $this->financial_report_model->getAllStudentsPaymentfct($students_ids, $start_date, $end_date, $residence_type, $payment_method);
                    } else {
                        $allStudents = [];
                    }
                    
                    return $allStudents;
                } else {
                    //specific student(s) selected
                    $students_ids = [];
                    array_push($students_ids, $student);
                    $allStudents = $this->financial_report_model->getAllStudentsPaymentfct($students_ids, $start_date, $end_date, $residence_type, $payment_method);
                    
                    return $allStudents;
                }

            }
        }
    } /*End of Students Payments Reports*/


    /*
    ====================================================================
    =========================== GET FUNCTIONS ==========================
    ====================================================================
    */


    //get only billed students owing
    function getAllBilledStudentsPaymentByDate($start_date, $end_date, $student_ids = array(), $residence_type=0, $bill_item='0', $payment_method='0') {

        if(count($student_ids) > 0) {
            //class selected
            //select all students from the payment table first
            $this->db->select('student_id');
            $this->db->from('payment');
            $this->db->where('can_delete !=', 'trash');
            $this->db->distinct();
            $this->db->where_in('student_id', $student_ids);

            if($residence_type != '0') {

                $this->db->where('residence_type', $residence_type);

            }

            if($bill_item != '0') {
                $this->db->where('title', $bill_item);
            }

            if($payment_method != '0') {
                $this->db->where('payment_method', $payment_method);
            }

            $this->db->where('invoice_id IS NOT NULL');
            $this->db->where('invoice_code IS NOT NULL');
            $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');

        } else {
            //select all students from the payment table first
            $this->db->select('student_id');
            $this->db->from('payment');
            $this->db->where('can_delete !=', 'trash');
            $this->db->distinct();

            if($residence_type != '0') {

                $this->db->where('residence_type', $residence_type);

            }

            if($bill_item != '0') {
                $this->db->where('title', $bill_item);
            }

            if($payment_method != '0') {
                $this->db->where('payment_method', $payment_method);
            }
            
            $this->db->where('invoice_id IS NOT NULL');
            $this->db->where('invoice_code IS NOT NULL');
            $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        }
        
        // Order by timestamp ascending (earliest payments first)
        $this->db->order_by('timestamp', 'asc');
        
        $billed_students = $this->db->get()->result_array(); 
        
        return $billed_students;
    } //end of billed students



    //get bill invoice codes owing by student id
    function getBillInvoicesPaymentByStudentId($start_date, $end_date, $student_id) {

        $this->db->select('invoice_code');
        $this->db->distinct();
        $this->db->where('student_id', $student_id);
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $invoice_code = $this->db->get('payment')->result_array(); 
        
        return $invoice_code;
    }


    //get total amount paid for billed invoice by student id
    function getTotalAmountPaidForBilledInvoiceByStudentId($start_date, $end_date, $student_id, $bill_item='0', $payment_method='0') {

        $this->db->select_sum('amount');
        $this->db->where('student_id', $student_id);
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        if($bill_item != '0') {
            $this->db->where('title', $bill_item);
        }
        if($payment_method != '0') {
            $this->db->where('payment_method', $payment_method);
        }
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $amountPaid = $this->db->get('payment')->row()->amount; 
        
        return $amountPaid;
    }

    function getTotalAmountPaidForBilledInvoiceByResidenceType($start_date, $end_date, $residence_type=0, $class_id='0', $student_id='0', $bill_item='0', $payment_method='0') {

        $this->db->select_sum('amount');
        if($residence_type != '0') {

            $this->db->where('residence_type', $residence_type);

        }

        if($class_id != '0') {

            $this->db->where('class_id', $class_id);

        }
        if($bill_item != '0') {
            $this->db->where('title', $bill_item);
        }

        if($payment_method != '0') {
            $this->db->where('payment_method', $payment_method);
        }

        if($student_id != '0') {

            $this->db->where('student_id', $student_id);

        }
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $amountPaid = $this->db->get('payment')->row()->amount; 
        
        return $amountPaid;
    }

    //get bill owing by invoice code
    function getBillPaymentByInvoiceCode($invoice_code, $start_date, $end_date) {

        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $this->db->where('can_delete !=', 'trash');
        $bills = $this->db->get('payment')->result_array(); 
        
        return $bills;
    }


    function getTotalOwingByStudentId($student_id = '') {

        $this->db->select_sum('due');
        $this->db->where('student_id', $student_id);
        $this->db->where('due >', '0');
        $this->db->where('can_delete !=', 'trash');
        $amountOwe = $this->db->get('invoice')->row()->due; 
        
        return $amountOwe;
    }

    //get bill owing by invoice code row
    function getBillPaymentRowByInvoiceCode($invoice_code, $start_date, $end_date) {

        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $bills = $this->db->get('payment')->row(); 
        
        return $bills;
    }

    //get only fedding fees, classes fees or transport fare
    function getAllStudentsPaymentfct($student_ids = array(), $start_date, $end_date, $residence_type=0, $payment_method='0') {
        $this->db->select('t.student_id');
        $this->db->distinct();
        $this->db->from('daily_fee_transactions t');
        $this->db->join('student s', 's.student_id = t.student_id');
        
        if(count($student_ids) > 0) {
            $this->db->where_in('t.student_id', $student_ids);
        }
        
        if($residence_type != '0') {
            // Use residence_type from the transaction table (captured at payment time)
            // This prevents duplicates when a student has multiple enrollment records
            $this->db->where('t.residence_type', $residence_type);
        }
        
        if($payment_method != '0') {
            $this->db->where('t.payment_method', $payment_method);
        }
        
        $this->db->where('t.payment_date >=', $start_date);
        $this->db->where('t.payment_date <=', $end_date);
        
        // Order by timestamp ascending (earliest payments first)
        $this->db->order_by('t.payment_date', 'asc');
        
        $result = $this->db->get()->result_array();
        
        return $result;
    } //end of feeding, classes and transport

    //get feeding, classes and transport fare paid by student id
    function getfctPaidByStudentId($student_id, $start_date, $end_date) {
        $this->db->select_sum('feeding_amount');
        $this->db->select_sum('breakfast_amount');
        $this->db->select_sum('classes_amount');
        $this->db->select_sum('water_amount');
        $this->db->select_sum('transport_amount');
        $this->db->where('student_id', $student_id);
        $this->db->where('payment_date >=', $start_date);
        $this->db->where('payment_date <=', $end_date);
        $row = $this->db->get('daily_fee_transactions')->row();
        
        $amountPaid = ($row->feeding_amount ?? 0) + ($row->breakfast_amount ?? 0) + ($row->classes_amount ?? 0) + ($row->water_amount ?? 0) + ($row->transport_amount ?? 0);
        return $amountPaid;
    }

    function getfctPaidByStudentIdOnaDay($student_id, $timestamp, $item) {
        $table = $item.'_fee_payment';

        $this->db->where('student_id', $student_id);
        $this->db->where('day_timestamp', $timestamp);
        $amountPaid = $this->db->get($table)->row()->amount;

        if($item == 'transport') {
            $this->db->where('student_id', $student_id);
            $this->db->where('day_timestamp', $timestamp);
            $amountPaid = $this->db->get('transport_fare_payment')->row()->amount;
        }


        return $amountPaid;       

    }

    function getTotalFctPaidByStudentIdOnaDay($timestamp, $item) {
        $table = $item.'_fee_payment';

        $this->db->select_sum('amount');
        $this->db->where('day_timestamp', $timestamp);
        $totalAmountPaid = $this->db->get($table)->row()->amount;

        if($item == 'transport') {
            $this->db->select_sum('amount');
            $this->db->where('day_timestamp', $timestamp);
            $totalAmountPaid = $this->db->get('transport_fare_payment')->row()->amount;
        }


        return $totalAmountPaid;       

    }
    

    function getfctPaidByStudentIdAndResidenceType($student_id, $start_date, $end_date, $residence_type=0) {
        $this->db->select_sum('t.feeding_amount');
        $this->db->select_sum('t.breakfast_amount');
        $this->db->select_sum('t.classes_amount');
        $this->db->select_sum('t.water_amount');
        $this->db->select_sum('t.transport_amount');
        $this->db->from('daily_fee_transactions t');
        $this->db->where('t.student_id', $student_id);
        
        if($residence_type != '0') {
            // Use residence_type from the transaction table (captured at payment time)
            // This prevents duplicates when a student has multiple enrollment records
            $this->db->where('t.residence_type', $residence_type);
        }
        
        $this->db->where('t.payment_date >=', $start_date);
        $this->db->where('t.payment_date <=', $end_date);
        $row = $this->db->get()->row();
        
        $amountPaid = ($row->feeding_amount ?? 0) + ($row->breakfast_amount ?? 0) + ($row->classes_amount ?? 0) + ($row->water_amount ?? 0) + ($row->transport_amount ?? 0);
        return $amountPaid;
    }

    // Get payment methods used by student for FCT (daily fees)
    function getPaymentMethodsUsedByStudentFct($start_date, $end_date, $student_id, $residence_type='0', $payment_method='0') {
        $this->db->select('payment_method');
        $this->db->distinct();
        $this->db->from('daily_fee_transactions t');
        $this->db->where('t.student_id', $student_id);
        $this->db->where('t.payment_date >=', $start_date);
        $this->db->where('t.payment_date <=', $end_date);
        
        if($residence_type != '0') {
            // Use residence_type from the transaction table (captured at payment time)
            // This prevents duplicates when a student has multiple enrollment records
            $this->db->where('t.residence_type', $residence_type);
        }
        if($payment_method != '0') {
            $this->db->where('t.payment_method', $payment_method);
        }
        
        $this->db->where('t.payment_method IS NOT NULL');
        $this->db->order_by('t.payment_method', 'asc');
        $result = $this->db->get()->result_array();
        
        return $result;
    }

    //get feeding, classes and transport fare by student id
    function getfctPaymentByStudentId($student_id, $start_date, $end_date) {

        //feeding fees only
        $this->db->select('title');
        $this->db->distinct();
        $this->db->where('student_id', $student_id);
        $this->db->where('payment_type', 'income');
        $this->db->where('invoice_id IS NULL');
        $this->db->where('invoice_code IS NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $result = $this->db->get('payment')->result_array();


        return $result;       

    } //end of feeding, classes and transport for each student

    //get total fct owing
    function getSumfctOwingByStudentId($student_id, $start_date, $end_date) {
        $wallet = $this->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row();
        
        if(!$wallet) return 0;
        
        $total = ($wallet->feeding_arrears > 0 ? $wallet->feeding_arrears : 0) +
                 ($wallet->breakfast_arrears > 0 ? $wallet->breakfast_arrears : 0) +
                 ($wallet->classes_arrears > 0 ? $wallet->classes_arrears : 0) +
                 ($wallet->water_arrears > 0 ? $wallet->water_arrears : 0) +
                 ($wallet->transport_arrears > 0 ? $wallet->transport_arrears : 0);
        
        return $total;
    } 

    //get the sum of feeding, classes and transport fare by student id
    function getSumfctPaymentByStudentId($fee, $column, $student_id, $start_date, $end_date) {

        //feeding fees only
        $this->db->select_sum($column);
        $this->db->where('student_id', $student_id);
        $this->db->where('title', $fee);
        $this->db->where('payment_type', 'income');
        $this->db->where('invoice_id IS NULL');
        $this->db->where('invoice_code IS NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $result = $this->db->get('payment')->row()->$column;


        return $result;       

    } //end of feeding, classes and transport for each student

    /*
    *====================================================================
       ================= END OF STUDENTS PAYMENTS REPORT===================
    *====================================================================
    */



    /*
    *====================================================================
       ================= START OF INCOME AND EXPENDITURE===================
    *====================================================================
    */
    /*Income & Expenditure Report Starts*/

    function incomeExpenditure($start_date, $end_date, $category) {
        if($category == 'income') {

            $allIncomeItems = $this->financial_report_model->getIncomeItems($start_date, $end_date);

            return $allIncomeItems;

        } else if($category == 'expenditure') {

            $allExpenditureItems = $this->financial_report_model->getExpenditureItems($start_date, $end_date);

            return $allExpenditureItems;

        } else {
            return false;
        }
    }

    //get functions

    /*
    ====================
    INCOME
    ====================
    */
    //get all income items
    function getIncomeItems($start_date, $end_date) {
        $this->db->select('title');
        $this->db->distinct();
        $this->db->where('payment_type', 'income');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $result = $this->db->get('payment')->result_array();

        return $result;
    }

    //sum of income item amount
    function getIncomeAmountByItem($start_date, $end_date, $item) {
        $this->db->select_sum('amount');
        $this->db->where('title', $item);
        $this->db->where('payment_type', 'income');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $result = $this->db->get('payment')->row()->amount;

        return $result;
    }


    /*
    ====================
    EXPENDITURE
    ====================
    */

    //get all expenditure items
    function getExpenditureItems($start_date, $end_date) {
        $this->db->select('title');
        $this->db->distinct();
        $this->db->where('payment_type', 'expense');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $result = $this->db->get('payment')->result_array();

        return $result;
    }

    //sum of expenditure item cost
    function getExpenditureAmountByItem($start_date, $end_date, $item) {
        $this->db->select_sum('amount');
        $this->db->where('title', $item);
        $this->db->where('payment_type', 'expense');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $result = $this->db->get('payment')->row()->amount;

        return $result;
    }

    /*
    *====================================================================
       ================= END OF INCOME AND EXPENDITURE REPORT===================
    *====================================================================
    */


    /*
    *====================================================================
       ================= GET AMOUNT OWED BY STUDENT ===================
    *====================================================================
    */

       function getFeeOwedByStudentId($student_id) {

        $this->db->select_sum('due');
        $this->db->from('invoice');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('student_id', $student_id);
        $feeOwed = $this->db->get()->row()->due;

        return $feeOwed;

       }


       /*
    *====================================================================
       =================    GET ALL ITEMS PAID FOR ===================
    *====================================================================
    */
    function getAllInvoiceItemsReceived($startDate, $endDate, $residence_type=0, $class_id='', $student_id=0, $bill_item='0', $payment_method='0') {
        
        $this->db->select('title');
        $this->db->distinct();
        $this->db->where('payment_type', 'income');
        if($residence_type != '0') {

            $this->db->where('residence_type', $residence_type);

        }

        if($class_id != '0') {

            $this->db->where('class_id', $class_id);

        }
        if($bill_item != '0') {
            $this->db->where('title', $bill_item);
        }
        if($student_id != '0') {

            $this->db->where('student_id', $student_id);

        }
        if($payment_method != '0') {
            $this->db->where('payment_method', $payment_method);
        }
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $startDate .'" AND "'. $endDate. '"');
        $array = $this->db->get('payment')->result_array();

        return $array;
    }

    function getAllBillItems() {

        $this->db->select('title');
        $this->db->distinct();
        $this->db->where('payment_type', 'income');
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $array = $this->db->get('payment')->result_array();

        return $array;
    }

    function getTotalAmountReceivedForItem($startDate, $endDate, $item, $residence_type=0, $class_id='0', $student_id=0, $payment_method='0') {

        $this->db->select_sum('amount');
        $this->db->where('payment_type', 'income');
        if($residence_type != '0') {

            $this->db->where('residence_type', $residence_type);

        }

        if($class_id != '0') {

            $this->db->where('class_id', $class_id);

        }
        if($student_id != '0') {

            $this->db->where('student_id', $student_id);

        }
        if($payment_method != '0') {
            $this->db->where('payment_method', $payment_method);
        }
        $this->db->where('title', $item);
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $startDate .'" AND "'. $endDate. '"');
        $amountReceived = $this->db->get('payment')->row()->amount;

        return $amountReceived;
    }

    // Get payment method breakdown for billed invoices
    function getPaymentMethodBreakdown($startDate, $endDate, $residence_type=0, $class_id='0', $student_id=0, $bill_item='0') {
        $this->db->select('payment_method, SUM(amount) as total_amount');
        $this->db->where('payment_type', 'income');
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $startDate .'" AND "'. $endDate. '"');
        
        if($residence_type != '0') {
            $this->db->where('residence_type', $residence_type);
        }
        if($class_id != '0') {
            $this->db->where('class_id', $class_id);
        }
        if($student_id != '0') {
            $this->db->where('student_id', $student_id);
        }
        if($bill_item != '0') {
            $this->db->where('title', $bill_item);
        }
        
        $this->db->group_by('payment_method');
        $this->db->order_by('payment_method', 'asc');
        $result = $this->db->get('payment')->result_array();
        
        return $result;
    }

    // Get individual payments for a student with payment method details
    function getStudentPaymentsWithMethod($startDate, $endDate, $student_id, $bill_item='0', $payment_method='0') {
        $this->db->select('payment_id, title, amount, payment_method, day_timestamp, receipt_code');
        $this->db->where('student_id', $student_id);
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $startDate .'" AND "'. $endDate. '"');
        
        if($bill_item != '0') {
            $this->db->where('title', $bill_item);
        }
        if($payment_method != '0') {
            $this->db->where('payment_method', $payment_method);
        }
        
        $this->db->order_by('day_timestamp', 'desc');
        $result = $this->db->get('payment')->result_array();
        
        return $result;
    }

    // Get payment methods used by a student (distinct list)
    function getPaymentMethodsUsedByStudent($startDate, $endDate, $student_id, $bill_item='0', $payment_method='0', $residence_type='0') {
        $this->db->select('payment_method');
        $this->db->distinct();
        $this->db->where('student_id', $student_id);
        $this->db->where('invoice_id IS NOT NULL');
        $this->db->where('invoice_code IS NOT NULL');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $startDate .'" AND "'. $endDate. '"');
        
        if($bill_item != '0') {
            $this->db->where('title', $bill_item);
        }
        if($payment_method != '0') {
            $this->db->where('payment_method', $payment_method);
        }
        if($residence_type != '0') {
            $this->db->where('residence_type', $residence_type);
        }
        
        $this->db->where('payment_method IS NOT NULL');
        $this->db->order_by('payment_method', 'asc');
        $result = $this->db->get('payment')->result_array();
        
        return $result;
    }


    /*get all bills history*/
    function getPreviousBillsHistory($class_ids_array = array(), $residence_type = 'Both') {
        $this->db->select_max('timestamp');
        $this->db->from('bill_item_history');
        $this->db->where_in('class_id', $class_ids_array);
        
        // Filter by residence type
        if($residence_type != 'Both') {
            $this->db->where_in('residence_type', array($residence_type, 'Both'));
        }
        
        $maxTimestamp = $this->db->get()->row()->timestamp;

        $this->db->select('bill_item_id');
        $this->db->distinct();
        $this->db->where_in('class_id', $class_ids_array);
        
        // Filter by residence type
        if($residence_type != 'Both') {
            $this->db->where_in('residence_type', array($residence_type, 'Both'));
        }
        
        $data_array = $this->db->get_where('bill_item_history', ['timestamp' => $maxTimestamp])->result_array();

        return $data_array;
    }

    /*get the bill item amount from the history table*/
    function getPreviousBillAmountHistory($bill_item_id, $residence_type = 'Both') {

        $this->db->where_in('bill_item_id', $bill_item_id);
        
        // Filter by residence type
        if($residence_type != 'Both') {
            $this->db->where_in('residence_type', array($residence_type, 'Both'));
        }
        
        $amount = $this->db->get('bill_item_history')->row()->bill_item_amount;

        return $amount;
    }

    function getBillsForNewAdmission($class_id, $running_year, $running_term, $residence_type) {

        // Get the class category for this class
        $class_row = $this->db->select('category')
            ->where('class_id', $class_id)
            ->get('class')
            ->row();
        
        if(!$class_row) {
            return array();
        }
        
        $class_category = $class_row->category;

        $dataArray = array(
            'class_id' => $class_id,
            'year' => $running_year,
            'term' => $running_term,
        );

        $residence_array = array($residence_type, 'Both');
        $this->db->where_in('residence_type', $residence_array);
        $billHistory = $this->db->get_where('bill_item_history', $dataArray)->result_array();

        // Remove duplicates: prioritize specific residence type over 'Both'
        $uniqueBills = array();
        $billItemIds = array();
        
        foreach($billHistory as $bill) {
            $itemId = $bill['bill_item_id'];
            
            if(!isset($billItemIds[$itemId])) {
                $billItemIds[$itemId] = $bill;
            } else {
                $existing = $billItemIds[$itemId];
                if($bill['residence_type'] == $residence_type && $existing['residence_type'] == 'Both') {
                    $billItemIds[$itemId] = $bill;
                }
            }
        }
        
        foreach($billItemIds as $bill) {
            $uniqueBills[] = $bill;
        }

        // Get the category ID for "New Admission-General"
        $category_row = $this->db->get_where('bill_category', ['bill_category_name' => 'New Admission-General'])->row();
        
        if($category_row) {
            // Get items directly from bill_item table with the category
            // Filter by class_category matching the student's class OR NULL (applies to all)
            $this->db->where('bill_category_id', $category_row->bill_category_id);
            $this->db->group_start();
            $this->db->where('class_category', $class_category);
            $this->db->or_where('class_category IS NULL');
            $this->db->or_where('class_category', '');
            $this->db->group_end();
            $generalItems = $this->db->get('bill_item')->result_array();
            
            // Convert to bill history format and check for duplicates
            foreach($generalItems as $item) {
                if(!isset($billItemIds[$item['id']])) {
                    $uniqueBills[] = array(
                        'bill_item_id' => $item['id'],
                        'bill_item_amount' => $item['amount']
                    );
                }
            }
        }

        return $uniqueBills;

    }

    function getTermlyClassBillsForStudent($class_id, $running_year, $running_term, $residence_type) {

        $dataArray = array(
            'class_id' => $class_id,
            'year' => $running_year,
            'term' => $running_term,
        );

        $residence_array = array($residence_type, 'Both');
        $this->db->where_in('residence_type', $residence_array);
        $billHistory = $this->db->get_where('bill_item_history', $dataArray)->result_array();

        // Remove duplicates: prioritize specific residence type over 'Both'
        $uniqueBills = array();
        $billItemIds = array();
        
        foreach($billHistory as $bill) {
            $itemId = $bill['bill_item_id'];
            
            if(!isset($billItemIds[$itemId])) {
                // First occurrence of this bill item
                $billItemIds[$itemId] = $bill;
            } else {
                // Duplicate found - keep the one matching student's residence type
                $existing = $billItemIds[$itemId];
                if($bill['residence_type'] == $residence_type && $existing['residence_type'] == 'Both') {
                    // Replace 'Both' with specific residence type
                    $billItemIds[$itemId] = $bill;
                }
                // If existing is already specific residence type, keep it
            }
        }
        
        // Convert back to indexed array
        foreach($billItemIds as $bill) {
            $uniqueBills[] = $bill;
        }

        return $uniqueBills;

    }

    function getClassTermlyBillsByResidenceTypeBatchInsertArray($class_id, $running_year, $running_term, $residence_type) {

        $dataArray = array(
            'class_id' => $class_id,
            'year' => $running_year,
            'term' => $running_term,
        );

        $residence_array = array($residence_type, 'Both');
        $this->db->where_in('residence_type', $residence_array);
        $billHistory = $this->db->get_where('bill_item_history', $dataArray)->result_array();

        // Remove duplicates: prioritize specific residence type over 'Both'
        $uniqueBills = array();
        $billItemIds = array();
        
        foreach($billHistory as $bill) {
            $itemId = $bill['bill_item_id'];
            
            if(!isset($billItemIds[$itemId])) {
                $billItemIds[$itemId] = $bill;
            } else {
                $existing = $billItemIds[$itemId];
                if($bill['residence_type'] == $residence_type && $existing['residence_type'] == 'Both') {
                    $billItemIds[$itemId] = $bill;
                }
            }
        }
        
        foreach($billItemIds as $bill) {
            $uniqueBills[] = $bill;
        }

        $batchDataInsert = array();

        foreach($uniqueBills as $bill) {

            
            $item_row = $this->getBillItemDetailRow($bill['bill_item_id']);

            $bill_data['student_id'] = $student_id;
            $bill_data['title'] = strtoupper(strtolower($item_row->title));
            $bill_data['description'] = $item_row->description;
            $bill_data['amount'] = $bill['bill_item_amount'];
            $bill_data['amount_paid'] = 0;
            $bill_data['due'] = $bill_data['amount'];
            $bill_data['status'] = 'unpaid';
            $bill_data['creation_timestamp'] = strtotime('today');

            $batchDataInsert[] = $bill_data;

        }

        return $batchDataInsert;

    }
    

    function getBillItemDetailRow($item_id) {

        return $this->db->get_where('bill_item', ['id' => $item_id])->row();

    }

    function getInvoiceCanDeleteStatus($invoice_code) {

        $this->db->where('invoice_code', $invoice_code);
        $can_delete = $this->db->get('invoice')->row()->can_delete;

        return $can_delete;
    }






    /*those who were billed for a particular term and year*/
    function getBilledStudents($dataArray=array()) { /*both term and year*/
        $this->db->select('student_id');
        $this->db->distinct();
        $invoice_list_array = $this->db->get_where('invoice', ['term' => $dataArray['term'], 'year' => $dataArray['year']])->result_array();

        echo '<option value="">Select Student</option>';
        foreach($invoice_list_array as $st) {

            $student_name = $this->crud_model->getStudentInfoById($st['student_id'])->name;
            $currenntEnrollmentRow = $this->crud_model->getStudentCurrentEnrollmentStatusRow($st['student_id']);

            $class_id = $currenntEnrollmentRow->class_id;
            $section_id = $currenntEnrollmentRow->section_id;
            $student_class = getFullClassName($class_id, $section_id);

            echo '<option value="'.$st['student_id'].'">'.$student_name.' - '.$student_class.'</option>';
        }
         
    }

     function getStudentInvoiceCodesByTermYear($dataArray=array()) { /*both term and year*/
        
    }
 }
