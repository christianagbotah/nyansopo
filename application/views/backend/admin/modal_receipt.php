<?php

//$invoice_code =      $param1;
$receipt_code = $param1;
$student_id = $param2;
// $year         =      $param4;
//$term         =      $param5;
$total_amount_paid = $param3;
$date = $param4;


//currency

$is_multi_invoice = false;

$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('ms_MS.utf8', NumberFormatter::DECIMAL);

$this->db->select('invoice_code');
$this->db->distinct();
$this->db->where('receipt_code', $receipt_code);
$this->db->where('student_id', $student_id);
$this->db->where('can_delete !=', 'trash');
$this->db->where('invoice_code IS NOT NULL'); // Exclude PREPAID CREDIT records
$invoice_array = $this->db->get('payment')->result_array();

$invoice_array2 = array();
$year_array = array();
$term_array = array();
$sem_array = array();

foreach ($invoice_array as $irow) {
	array_push($invoice_array2, $irow['invoice_code']);
}

if (count($invoice_array2) > 1) {

	$is_multi_invoice = true;

	//find all the various years, terms or semesters
	for ($i = 0; $i < count($invoice_array2); $i++) {
		//for year
		$yr = explode('-', $this->db->get_where('invoice', array('invoice_code' => $invoice_array2[$i], 'student_id' => $student_id))->row()->year)[1];
		array_push($year_array, $yr);
		$year_array = array_unique($year_array);

		//for term
		$tr = $this->db->get_where('invoice', array('invoice_code' => $invoice_array2[$i], 'student_id' => $student_id))->row()->term;
		array_push($term_array, $tr);
		$term_array = array_unique($term_array);

		//for semester
		$sr = $this->db->get_where('invoice', array('invoice_code' => $invoice_array2[$i], 'student_id' => $student_id))->row()->sem;
		array_push($sem_array, $sr);
		$sem_array = array_unique($sem_array);
	}

	$invoice_code = implode(', ', $invoice_array2);
	$year = implode(', ', $year_array);
	$term = implode(', ', $term_array);
	$sem = implode(', ', $sem_array);

} else {

	// Get payment record that has an invoice_code (not the PREPAID CREDIT record)
	$invoice_code = $this->db->get_where('payment', array(
		'receipt_code' => $receipt_code, 
		'student_id' => $student_id,
		'invoice_code !=' => NULL
	))->row()->invoice_code;

	$invoice_query = $this->db->get_where('invoice', array('invoice_code' => $invoice_code))->row();
	$year = $invoice_query->year;
	$term = $invoice_query->term;
	$sem = $invoice_query->sem;
}

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$running_sem = get_settings('running_sem');

$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
$system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
$system_address = $this->db->get_where('settings', array('type' => 'address'))->row();
$system_address = $system_address ? $system_address->description : '';
$system_slogan = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;
$cashier = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type') . '_id' => $this->session->userdata('login_user_id')))->row()->name;
$student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
$class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'mute' => '0', 'year' => $running_year))->row()->class_id;

$class = getFullClassName($class_id);

/*$class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
$class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;*/

//get total for this invoice code
/*$this->db->select_sum('amount');
	            $this->db->from('invoice');
	            $this->db->where('due !=', 0);
	            $this->db->where('student_id', $student_id);
	           // $this->db->where('term', $term);
	           // $this->db->where('year', $year);
	            $amount_total_array = $this->db->get()->result_array();

	            $amount_counter = 0;
	            foreach($amount_total_array as $arow) {
	                $amount_counter += $arow['amount'];
	            }

	            //test if the student owes
	            $this->db->select_sum('due');
	            $this->db->from('payment');
	            $this->db->where('receipt_code', $receipt_code);
	            $this->db->where('student_id', $student_id);
	            $any_bal = $this->db->get()->result_array();

	            $bal_counter = 0;
	            foreach($any_bal as $brow) {
	                $bal_counter += $brow['due'];
	            }

	            if($bal_counter == 0) {
	                //get total balance for this invoice code
	                $this->db->select_sum('amount_paid');
	                $this->db->from('invoice');
	                $this->db->where('due !=', 0);
	                $this->db->where('student_id', $student_id);
	               // $this->db->where('term', $term);
	               // $this->db->where('year', $year);
	                $amount_due_array = $this->db->get()->result_array();

	                $due_counter = 0;
	                foreach($amount_due_array as $arow) {
	                    $due_counter += $arow['amount_paid'];
	                }

	                $balance_owe = $amount_counter - $due_counter;
	            } else {
	                $this->db->where('receipt_code', $receipt_code);
	                $this->db->where('student_id', $student_id);
	               // $this->db->where('term', $term);
	               // $this->db->where('year', $year);
	                $balance_owe = $this->db->get('payment')->row()->due;
*/

// $this->db->select_sum('due');
// $this->db->where('receipt_code', $receipt_code);
// $this->db->where('student_id', $student_id);
// $this->db->where('can_delete !=', 'trash');
// // $this->db->where('term', $term);
// // $this->db->where('year', $year);
// $balance_owe = $this->db->get('payment')->row()->due;

$balance_owe = $this->financial_report_model->getFeeOwedByStudentId($student_id);
$total_amount_payable = $balance_owe + $total_amount_paid;

//add section A or B if the class has more than one section
/*$section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
$class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
$sec_name = '';
if ($class_has_more_sections > 1) {
	$sec_name = $section_name;
}

$class = '';
if ($class_name == 'CRECHE') {
	$class = ucwords(strtolower($class_name));
} else {
	$class = ucwords(strtolower($class_name)) . ' ' . $class_name_numeric . $sec_name;
}*/

//who issued the receipt
$issuer_id = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->issuer_id;

$issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name;
$account_type = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->account_type;

if (!empty($issuer_name) && !empty($account_type)) {
	$issuer_name = $issuer_name;
} else {
	$issuer_name = 'Not Available';
}

// Get invoice discounts for this student using new discount system
$invoice_discounts = array();
$discount_assignments = $this->db->where('student_id', $student_id)
    ->where('discount_category', 'invoice')
    ->where('status', 'approved')
    ->get('student_discount_assignments')->result_array();

foreach($discount_assignments as $assignment) {
    $profile = $this->db->where('id', $assignment['profile_id'])
        ->get('discount_profiles')->row();
    if($profile) {
        $invoice_discounts[] = array(
            'discount_type' => $assignment['discount_type'],
            'discount_percentage' => $profile->discount_method == 'percentage' ? $profile->discount_value : 0,
            'discount_amount' => $profile->discount_method == 'fixed_amount' ? $profile->discount_value : 0
        );
    }
}

?>
        <style type="text/css">
            #details {
               margin-left: 37px;
            }

            #watermark {
                z-index: -1;
                position: absolute;
                font-size: 35px;
                transform: rotate(-35deg);
                margin-top: 70px;
                color: #a09e9f;
            }

            @media Print
             {
                #details {
                    margin-left: 60px;
                }

                page {
                background: #ffffff;
                display: block;
                margin: 0 auto;
                margin-bottom: 0.5cm;
                box-shadow: 0;

                }

                page[size="A6"] {
                    width: 80mm;
                }

                body, page {
                    margin: 0;
                    box-shadow: 0;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                #watermark {
                    z-index: -1;
                    position: absolute;
                    font-size: 35px;
                    transform: rotate(-35deg);
                    margin-top: 70px;
                    color: #a09e9f;
                }
            }

        </style>
        <center>
<page size="A6" id="r_print">
    <div>
            <table class="table" style="border: 1px solid #d0cccc; padding: 2px 5px 2px 5px;">
            <tr>
            <td>
            <center>
            <div><img src="<?=base_url('uploads/school_logo.png');?>" style="max-height : 120px;"></div><br>
            <div><strong><?=$system_name;?></strong></div>
            <?php if(!empty($system_address)): ?>
            <div><strong><?=nl2br($system_address);?></strong></div>
            <?php endif; ?>
            <div><strong><?=$system_phone . ' | ' . $system_mail;?></strong></div>

            <div class="row" style="padding: 8px;" >
                <center><strong id="official" style="background-color: #000; width: 200px; padding: 6px; border-radius: 9px; color: #fff; font-size: 23px">Official Receipt</strong></center>
            </div>
            </center>

            <center><div>
                    <table class="table" id="details">
                        <thead>
                            <tr>
                                <th style="text-align: left;" colspan="2">Received From:</th>
                                <th style="text-align: right"><?=ucwords(strtolower($student_name));?></th>
                            </tr>
                            <?php
if ($class_name == 'JHSS') {
	?>
                            <tr>
                                <th style="text-align: left" colspan="2">Year | Semester:</th>
                                <th style="text-align: right"><?=$is_multi_invoice ? $year . ' | ' . $sem : explode('-', $year)[1] . ' | ' . $sem;?></th>
                            </tr>
                        <?php
} else {?>
                            <tr>
                                <th style="text-align: left" colspan="2">Year | Term:</th>
                                <th style="text-align: right"><?=$is_multi_invoice ? $year . ' | ' . $term : explode('-', $year)[1] . ' | ' . $term;?></th>
                            </tr>
                        <?php
}
?>
                            <tr>
                                <th style="text-align: left" colspan="2">Class:</th>
                                <th style="text-align: right"><?=$class;?></th>
                            </tr>
                            <tr>
                                <th style="text-align: left" colspan="2">Paid On:</th>
                                <th style="text-align: right"><?=date('d/m/Y H:i:s', $date);?></th>
                            </tr>
                            <tr>
                                <th style="text-align: left" colspan="2">Receipt No.:</th>
                                <th style="text-align: right"><?=$receipt_code;?></th>
                            </tr>
                            <tr>
                                <th style="text-align: left" colspan="2">Invoice No.:</th>
                                <th style="text-align: right"><?=$invoice_code;?></th>
                            </tr>
                            <tr>
                                <th style="text-align: left">Currency:</th>
                                <th style="text-align: right" colspan="2"><?=$currency;?></th>
                            </tr>
                            <tr>
                          <th style="text-align: left">Issued On:</th>
                          <th style="text-align: right" colspan="2"><?=date('d/m/Y H:i:s')?></th>
                          <th></th>
                          <th align="right"></th>
                        </tr>
                        </thead>
                    </table>
                </div></center><br>
                <table>
                    <thead>
                        <tr >
                            <th width="40" align="left">S/N</th>
                            <th width="180" align="left">ITEM</th>
                            <th></th>
                            <th width="120" style="text-align: right">PAID</th>
                        </tr>
                        <tr><th colspan="4"><hr></th></tr>
                    </thead>
                    <tbody>

                        <?php
$receipt_querry = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->result_array();

$i = 1;
foreach ($receipt_querry as $row) {

	echo '
                                    <tr>
                                        <td>' . $i . '</td>
                                        <td align="left" colspan="2" style="font-size: 12px">' . $row['title'] . '</td>
                                        <td align="right">' . number_format($row['amount'], 2, '.', ',') . '</td>
                                    </tr>
                                ';

	$i++;

}

?>
                        <tr></tr>
                        <tr></tr>
                        <tr></tr>
                        <tr><td colspan="4"><hr></td></tr>
                        <!-- <tr>
                            <td align="right" colspan="3">Sub-total:</td>
                            <td align="right"><?=number_format($total_amount_paid, 2, '.', ',');?></td>
                        </tr> -->
                        <tr>
                            <td align="right" colspan="3">Amount Paid:</td>
                            <td align="right" style="font-weight: bolder;"><?=number_format($total_amount_paid, 2, '.', ',');?></td>
                        </tr>
                        <tr>
                            <td align="right" colspan="3">Total Payable:</td>
                            <td align="right"><?=number_format($total_amount_payable, 2, '.', ',');?></td>
                        </tr>
                        <tr>
                            <?php if ($balance_owe < 0): ?>
                            <td align="right" colspan="3" style="color: #10b981; font-weight: bolder;">Credit Balance:</td>
                            <td align="right" style="font-weight: bolder; color: #10b981;"><?=number_format($balance_owe, 2, '.', ',');?></td>
                            <?php else: ?>
                            <td align="right" colspan="3">Arrears:</td>
                            <td align="right" style="font-weight: bolder;"><?=number_format($balance_owe, 2, '.', ',');?></td>
                            <?php endif; ?>
                        </tr>
                        
                        <?php
                        // Check if credit was created from this payment
                        if(file_exists(APPPATH . 'models/Credit_model.php')) {
                            $this->load->model('Credit_model');
                            $credit_check = $this->db->get_where('student_credits', [
                                'source_receipt_code' => $receipt_code,
                                'student_id' => $student_id
                            ])->row();
                            
                            if($credit_check && $credit_check->credit_amount > 0):
                        ?>
                        <tr><td colspan="4"><hr style="border-top: 2px solid #28a745;"></td></tr>
                        <tr style="background: #d4edda;">
                            <td colspan="4" align="center" style="padding: 8px; color: #155724; font-weight: bold; font-size: 11px;">
                                <i class="fa fa-gift"></i> CREDIT CREATED: GH₵ <?=number_format($credit_check->credit_amount, 2)?>
                                <div style="font-size: 9px; font-weight: normal; margin-top: 2px;">Applied to future bills</div>
                            </td>
                        </tr>
                        <?php 
                            endif;
                        }
                        ?>
                        <?php if(!empty($invoice_discounts)): ?>
                        <tr><td colspan="4"><hr></td></tr>
                        <tr>
                            <td colspan="4" align="center" style="font-weight: bold; font-size: 11px;">DISCOUNT APPLIED</td>
                        </tr>
                        <?php foreach($invoice_discounts as $disc): ?>
                        <tr>
                            <td colspan="3" align="right" style="font-size: 10px;"><?=ucwords($disc['discount_type'])?> 
                                <?php if($disc['discount_percentage'] > 0): ?>
                                    (<?=$disc['discount_percentage']?>%):
                                <?php else: ?>
                                    (Fixed):
                                <?php endif; ?>
                            </td>
                            <td align="right" style="font-size: 10px;">
                                <?php if($disc['discount_percentage'] > 0): ?>
                                    -<?=number_format(($disc['discount_percentage']/100) * $total_amount_payable, 2, '.', ',')?>
                                <?php else: ?>
                                    -<?=number_format($disc['discount_amount'], 2, '.', ',')?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        <tr><td><br></td></tr>
                        <tr>
                            <td colspan="2">
                                <strong>Cashier: </strong><br><?=ucwords(strtolower($issuer_name));?>
                                <hr>
                            </td>
                            <?php
$method = ucwords($this->db->get_where('payment', array('receipt_code' => $receipt_code))->row()->payment_method);

?>
                        <td colspan="2" align="right"><strong>Method: </strong><br><?=$method;?> <hr></td>
                    </tr>

                        </tr>
                        <tr><td colspan="4" align="center">
                            <p><?=$system_slogan . "!!!";?></p>
                            <p><strong>Thank You</strong></p>
                        </td></tr>
                        <?php
if ($is_multi_invoice) {
	//multi invoice receipt

	?>
                                <div id="watermark">Payment for multi-invoices</div>
                            <?php

} else {
	//single invoice receipt

	if ($class_name == 'JHSS') {
		if ($year != $running_year || $sem != $running_sem) {
			?>
                                        <div id="watermark">Payment of Arrears!!!</div>
                                    <?php
}
	} else {
		if ($year != $running_year || $term != $running_term) {
			?>
                                        <div id="watermark">Payment of Arrears!!!</div>
                                    <?php
}
	}
}
?>

                    </tbody>
                </table>
            </td>
            </tr>
            </table><br>
    </div>
 </page>
        <button onclick="PrintElem('#r_print')" style="border: 1 solid #0cc; padding: 10px; cursor: pointer; background-color: green; color: #fff;">Print</button>
        </center>


    <script type="text/javascript">
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'Receipt', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title><style>#watermark {position: absolute;font-size: 28px;transform: rotate(-35deg);margin-top: 70px;color: #a09e9f;}</style>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body style="font-size: 14px">');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }

    }
</script>