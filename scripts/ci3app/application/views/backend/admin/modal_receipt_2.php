<?php

$receipt_code = $param1;
$student_id = $param2;

$total_amount_paid = $param3;
$date = $param4;

//currency
$is_multi_invoice = false;

$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$this->db->select('invoice_code');
$this->db->distinct();
$this->db->where('receipt_code', $receipt_code);
$this->db->where('student_id', $student_id);
$this->db->where('can_delete !=', 'trash');
$this->db->where('invoice_code IS NOT NULL'); // Exclude PREPAID CREDIT records
$invoice_array = $this->db->get('payment')->result_array();

//items names
$this->db->select('title');
$this->db->distinct();
$this->db->where('receipt_code', $receipt_code);
$this->db->where('student_id', $student_id);
$this->db->where('can_delete !=', 'trash');
$titles_array = $this->db->get('payment')->result_array();

$payment_row_query = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row();

//who issued the receipt
$issuer_id = $payment_row_query->issuer_id;
$account_type = $payment_row_query->account_type;
$method = ucwords($payment_row_query->payment_method);
$transaction_id = $payment_row_query->transaction_id;
$cheque_number = $payment_row_query->cheque_number;
$bank_name = $payment_row_query->bank_name;

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
$box_number = $this->db->get_where('settings', array('type' => 'box_number'))->row()->description;
$digital_address = $this->db->get_where('settings', array('type' => 'digital_address'))->row()->description;
$location = $this->db->get_where('settings', array('type' => 'location'))->row()->description;
$student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
$class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year))->row()->class_id;
$class = getFullClassName($class_id);

$balance_owe = $this->financial_report_model->getFeeOwedByStudentId($student_id);
$total_amount_payable = $balance_owe + $total_amount_paid;

//who issued the receipt
$issuer_id = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->issuer_id;

$issuer_data = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row();
$issuer_name = '';

if (!empty($issuer_data)) {
	$first_name = ucfirst(strtolower(explode(' ', $issuer_data->name)[0]));
	$prefix = ($issuer_data->gender == 'male') ? 'Sir' : (($issuer_data->gender == 'female') ? 'Madam' : '');
	$issuer_name = trim($prefix . ' ' . $first_name);
} else {
	$issuer_name = 'Account Office';
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


                page {
                background: #ffffff;
                display: block;
                margin: 0 auto;
                margin-bottom: 0.5cm;
                box-shadow: 0;

                }

                page[size="A5"] {
                    width: 210mm;
                    height: 148.5mm;
                }

                body, page {
                    margin: 0;
                    box-shadow: 0;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    padding: 5px 0px;
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

<page size="A5" id="r_print">
        <div id="main_receipt">

            <center>
                <div class="row"><u><h2><?=$system_name;?></h2></u></div>

                <table style="width: 100%">
                    <tbody>
                        <tr>
                            <td width="30%">
                                <center>
                                    <img src="<?=base_url('uploads/school_logo.png');?>" style="max-height : 80px;">
                                </center>
                            </td>
                            <td width="40%">
                                <center>
                                    <div class="row">
                                        <strong><?=$location;?></strong>
                                    </div>
                                    <?php if(!empty($system_address)): ?>
                                    <div class="row">
                                        <strong><?=nl2br($system_address);?></strong>
                                    </div>
                                    <?php endif; ?>
                                    <div class="row">
                                        <strong><?=$box_number . ' | ' . $digital_address;?></strong>
                                    </div>
                                    <div class="row">
                                        <strong><?=$system_phone;?></strong>
                                    </div>
                                    <?php if(!empty($system_mail)): ?>
                                    <div class="row">
                                        <strong><?=$system_mail;?></strong>
                                    </div>
                                    <?php endif; ?>

                                    <div class="row" style="padding: 8px; margin-top: 10px">
                                        <strong id="official" style="background-color: #000; width: 200px; padding: 6px; border-radius: 9px; color: #fff; font-size: 23px; text-transform: uppercase;">Official Receipt</strong>
                                    </div>
                                </center>

                            </td>
                            <td width="30%" align="right">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td align="right">Year:</td>
                                            <td align="right"><strong><?=$year;?></strong></td>
                                        </tr>
                                        <tr>
                                            <td align="right">Term:</td>
                                            <td align="right"><strong><?=$term;?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </center>
            <br>

            <div>
                <table style="width: 100%" id="receipt_table">
                    <thead>
                        <tr style="font-size: 14px;">
                            <th style="text-align: left">RECEIPT N<sup><u>o</u></sup>:</th>
                            <th style="text-align: left">#<?=$receipt_code;?></th>
                            <th colspan="2" style="text-align: left">INVOICE N<sup><u>o</u></sup>:</th>
                            <th style="text-align: left">#<?=$invoice_code;?></th>
                            <th style="text-align: right">PAID ON:</th>
                            <th style="text-align: right" colspan="2"><?=date('d/m/Y H:i:s', $date);?></th>
                        </tr>
                        <tr><th colspan="8" style="border-bottom: 2px solid #211f1f;"></th></tr>
                        <tr><th colspan="8"></th></tr>
                        <tr><th colspan="8"></th></tr>
                        <tr><th colspan="8"></th></tr>

                    </thead>
                    <tbody>
                        <tr>
                            <td align="left">Received from:</td>
                            <td align="left" colspan="5" style="border-bottom: 2px dotted #7c7878;"><?=strtoupper(strtolower($student_name));?></td>
                            <td align="right">Class:</td>
                            <td align="right" style="border-bottom: 2px dotted #7c7878;"><?=$class;?></td>
                        </tr>

                        <?php
                            //currencies - Fix the pessewa issue
                            $currencyInWords = addCurrencyInWords($currency, $total_amount_paid);
                            $amountInWords = numberToWordConverter($total_amount_paid);
                            
                            // Remove pesewas if amount is whole number
                            $decimal_part = ($total_amount_paid - floor($total_amount_paid)) * 100;
                            if($decimal_part == 0) {
                                // Remove pesewas part from the words
                                $amountInWords = preg_replace('/, Zero Pesewas Only\.?$/i', ' Only.', $amountInWords . ' ' . $currencyInWords);
                                $amountInWords = preg_replace('/, Zero Pesewas$/i', '', $amountInWords);
                            } else {
                                $amountInWords = $amountInWords . ' ' . $currencyInWords;
                            }
                            
                            // Ensure proper capitalization (Ghana Cedis, not ghana cedis)
                            $amountInWords = ucfirst($amountInWords);
                            $amountInWords = str_replace('ghana cedis', 'Ghana Cedis', $amountInWords);

                            if ($balance_owe == 0) {
                                $paymentStatus = 'Full payment';
                            } else if ($balance_owe > 0) {
                                $paymentStatus = 'Part payment';
                            } else {
                                $paymentStatus = 'Overpayment - Credit Available';
                            }
                        ?>

                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr>
                            <td align="left">The Sum of:</td>
                            <td align="left" style="border-bottom: 2px dotted #7c7878;" colspan="7"><?=$amountInWords;?></td>
                        </tr>

                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr>
                            <td align="left">Being:</td>
                            <td align="left" colspan="7" style="border-bottom: 2px dotted #7c7878;">
                                <?php
                                    echo $paymentStatus . ' for Fees.';
                                ?>
                            </td>

                        </tr>

                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        
                        <?php
                            /*We display this section only if transaction is cheque or momo*/
                            if($method == 'cheque' || $method == 'momo'):
                        ?>
                        <tr>
                            <td align="left"><?=$method == 'cheque' ? 'Cheque N<sup><u>o</u></sup>' : 'Momo Transaction ID'; ?>:</td>
                            <td align="left" colspan="7" style="border-bottom: 2px dotted #7c7878;">
                                <?=$method == 'cheque' ? $bank_name.' | '. $cheque_number : $transaction_id; ?>
                            </td>
                        </tr>

                        <?php
                        endif;
                        ?>

                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr>
                            <td align="left">Amount Paid:</td>
                            <td align="left" colspan="4" style="border-bottom: 2px dotted #7c7878;"><?=numfmt_format_currency($fmt, $total_amount_paid, $currency);?></td>
                            <?php if ($balance_owe < 0): ?>
                            <td align="right" style="color: #10b981; font-weight: bold;">Credit Balance:</td>
                            <td align="right" colspan="2" style="border-bottom: 2px dotted #7c7878; color: #10b981; font-weight: bold;"><?=numfmt_format_currency($fmt, $balance_owe, $currency);?></td>
                            <?php else: ?>
                            <td align="right">Balance:</td>
                            <td align="right" colspan="2" style="border-bottom: 2px dotted #7c7878;"><?=numfmt_format_currency($fmt, $balance_owe, $currency);?></td>
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
                        <tr><td colspan="8" style="height: 10px;"></td></tr>
                        <tr style="background: linear-gradient(135deg, #d4edda, #c3e6cb);">
                            <td colspan="8" align="center" style="padding: 10px; border-top: 2px solid #28a745;">
                                <div style="color: #155724; font-weight: bold; font-size: 13px;">
                                    <i class="fa fa-gift" style="margin-right: 5px;"></i>
                                    CREDIT CREATED: <?=numfmt_format_currency($fmt, $credit_check->credit_amount, $currency);?>
                                </div>
                                <div style="color: #155724; font-size: 10px; margin-top: 2px;">
                                    Will be applied to future bills
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endif;
                        }
                        ?>
                        <?php if(!empty($invoice_discounts)): ?>
                        <tr><td colspan="8"></td></tr>
                        <tr>
                            <td colspan="8" align="center" style="font-weight: bold;">DISCOUNT APPLIED</td>
                        </tr>
                        <?php foreach($invoice_discounts as $disc): ?>
                        <tr>
                            <td colspan="6" align="right"><?=ucwords($disc['discount_type'])?> Discount 
                                <?php if($disc['discount_percentage'] > 0): ?>
                                    (<?=$disc['discount_percentage']?>%):
                                <?php else: ?>
                                    (Fixed):
                                <?php endif; ?>
                            </td>
                            <td align="right" colspan="2">
                                <?php if($disc['discount_percentage'] > 0): ?>
                                    -<?=numfmt_format_currency($fmt, ($disc['discount_percentage']/100) * $total_amount_payable, $currency)?>
                                <?php else: ?>
                                    -<?=numfmt_format_currency($fmt, $disc['discount_amount'], $currency)?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>
                        <tr><td colspan="8"></td></tr>

                        <tr>
                            <td align="right" colspan="6">Bursar's Signature:</td>
                            <td align="right" colspan="2" style="border-bottom: 2px dotted #7c7878;"></td>
                        </tr>
                        <tr><td colspan="8"></td></tr>
                        <tr>
                            <td align="right" colspan="6">Issued By:</td>
                            <td align="right" colspan="2"><strong><?=$issuer_name;?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <center>
                <div class="row">
                    <p><strong>Thank You For Your Payment</strong></p>
                </div>
            </center>
        </div>
     </page><br>

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
