<?php

$receipt_code = $param1;
$student_id = $param2;

$total_amount_paid = $param3;
$date = $param4;

?>
<style type="text/css">
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 11px;
        line-height: 1.4;
        color: #2c3e50;
        background: #ffffff;
    }
    
    page {
        display: block;
        margin: 0 auto;
        background: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 15mm;
        position: relative;
        min-height: 210mm;
    }
    
    page[size="A5"] {
        width: 148mm;
        height: 210mm;
        max-width: 148mm;
        max-height: 210mm;
    }
    
    .header-section {
        border-bottom: 2px solid #34495e;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    
    .school-logo {
        max-height: 45px;
        width: auto;
    }
    
    .school-info {
        text-align: center;
    }
    
    .school-name {
        font-size: 18px;
        font-weight: bold;
        color: #2c3e50;
        text-decoration: underline;
        margin-bottom: 5px;
        line-height: 1.2;
        word-wrap: break-word;
        white-space: normal;
    }
    
    .school-details {
        font-size: 9px;
        color: #34495e;
        line-height: 1.3;
    }
    
    .receipt-badge {
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .receipt-info {
        font-size: 9px;
        font-weight: 600;
    }
    
    .bills-section {
        margin: 12px 0;
    }
    
    .section-title {
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        margin: 8px 0;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .bills-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        font-size: 10px;
    }
    
    .bills-table th {
        background: linear-gradient(135deg, #34495e, #2c3e50);
        color: white;
        padding: 6px 4px;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .bills-table td {
        padding: 4px;
        border: 1px solid #bdc3c7;
    }
    
    .bills-table .amount-cell {
        text-align: right;
        font-weight: 600;
    }
    
    .discount-row {
        background: linear-gradient(135deg, #d5f4e6, #a8e6cf);
        color: #27ae60;
        font-weight: bold;
    }
    
    .total-row {
        background: linear-gradient(135deg, #ecf0f1, #d5dbdb);
        font-weight: bold;
        font-size: 11px;
    }
    
    .receipt-details {
        margin-top: 15px;
    }
    
    .receipt-table {
        width: 100%;
        font-size: 10px;
        line-height: 1.5;
    }
    
    .receipt-table td {
        padding: 3px 2px;
        vertical-align: top;
    }
    
    .receipt-header {
        border-bottom: 1px solid #34495e;
        padding-bottom: 4px;
        margin-bottom: 8px;
        font-weight: bold;
        font-size: 9px;
    }
    
    .student-name {
        font-weight: bold;
        text-transform: uppercase;
        color: #2c3e50;
    }
    
    .amount-words {
        font-style: italic;
        color: #34495e;
        text-transform: none;
        text-align: left;
    }
    
    .signature-section {
        margin-top: 15px;
        border-top: 1px solid #bdc3c7;
        padding-top: 8px;
    }
    
    .signature-line {
        border-bottom: 1px dotted #7f8c8d;
        min-height: 20px;
    }
    
    .issuer-name {
        font-weight: bold;
        color: #2c3e50;
    }
    
    .print-buttons {
        text-align: center;
        margin-top: 20px;
    }
    
    .btn {
        padding: 10px 20px;
        margin: 0 5px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-print {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    @media print {
        body {
            margin: 0;
            padding: 0;
            font-size: 10px;
        }
        
        page {
            box-shadow: none;
            margin: 0;
            padding: 10mm;
            display: block !important;
        }
        
        page[size="A5"] {
            width: 148mm;
            height: 210mm;
        }
        
        .print-buttons {
            display: none;
        }
        
        .header-section {
            display: block !important;
            border-bottom: 2px solid #34495e !important;
            padding-bottom: 8px !important;
            margin-bottom: 12px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .header-section table {
            width: 100% !important;
            display: table !important;
        }
        
        .header-section td {
            display: table-cell !important;
            vertical-align: top !important;
        }
        
        .school-logo {
            max-height: 45px !important;
            width: auto !important;
            display: block !important;
        }
        
        .school-name {
            font-size: 16px !important;
            font-weight: bold !important;
            color: #2c3e50 !important;
            text-decoration: underline !important;
            margin-bottom: 5px !important;
            line-height: 1.2 !important;
            display: block !important;
        }
        
        .school-details {
            font-size: 9px !important;
            color: #34495e !important;
            line-height: 1.3 !important;
        }
        
        .receipt-badge {
            background: #2c3e50 !important;
            color: white !important;
            padding: 6px 12px !important;
            border-radius: 8px !important;
            font-size: 10px !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            display: inline-block !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .bills-section {
            margin: 12px 0 !important;
            display: block !important;
        }
        
        .section-title {
            font-size: 12px !important;
            font-weight: bold !important;
            text-align: center !important;
            margin: 8px 0 !important;
            color: #2c3e50 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        .bills-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 8px !important;
            font-size: 9px !important;
            display: table !important;
        }
        
        .bills-table thead,
        .bills-table tbody,
        .bills-table tfoot {
            display: table-row-group !important;
        }
        
        .bills-table tr {
            display: table-row !important;
        }
        
        .bills-table th,
        .bills-table td {
            display: table-cell !important;
            border: 1px solid #bdc3c7 !important;
            padding: 4px !important;
        }
        
        .bills-table th {
            background: #34495e !important;
            color: white !important;
            font-size: 9px !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .bills-table .amount-cell {
            text-align: right !important;
            font-weight: 600 !important;
        }
        
        .discount-row {
            background: #d5f4e6 !important;
            color: #27ae60 !important;
            font-weight: bold !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .total-row {
            background: #ecf0f1 !important;
            font-weight: bold !important;
            font-size: 10px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .receipt-details {
            margin-top: 15px !important;
            display: block !important;
        }
        
        .receipt-table {
            width: 100% !important;
            font-size: 9px !important;
            line-height: 1.5 !important;
            display: table !important;
        }
        
        .receipt-table thead,
        .receipt-table tbody {
            display: table-row-group !important;
        }
        
        .receipt-table tr {
            display: table-row !important;
        }
        
        .receipt-table td,
        .receipt-table th {
            display: table-cell !important;
            padding: 3px 2px !important;
            vertical-align: top !important;
        }
        
        .receipt-header {
            border-bottom: 1px solid #34495e !important;
            padding-bottom: 4px !important;
            margin-bottom: 8px !important;
            font-weight: bold !important;
            font-size: 9px !important;
        }
        
        .student-name {
            font-weight: bold !important;
            text-transform: uppercase !important;
            color: #2c3e50 !important;
        }
        
        .amount-words {
            font-style: italic !important;
            color: #34495e !important;
            text-transform: none !important;
            text-align: left !important;
        }
        
        .signature-section {
            margin-top: 15px !important;
            border-top: 1px solid #bdc3c7 !important;
            padding-top: 8px !important;
            display: block !important;
        }
        
        .signature-line {
            border-bottom: 1px dotted #7f8c8d !important;
            min-height: 20px !important;
        }
        
        .issuer-name {
            font-weight: bold !important;
            color: #2c3e50 !important;
        }
        
        .thank-you-footer {
            position: absolute !important;
            bottom: 15mm !important;
            left: 15mm !important;
            right: 15mm !important;
            text-align: center !important;
            padding-top: 15px !important;
            border-top: 1px solid #bdc3c7 !important;
            background: white !important;
            display: block !important;
        }
        
        .thank-you-footer div {
            display: block !important;
        }
    }

    #watermark {
        z-index: -1;
        position: absolute;
        font-size: 28px;
        transform: rotate(-35deg);
        margin-top: 50px;
        color: #a09e9f;
    }
</style>

<?php



//currency
$is_multi_invoice = false;

$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$this->db->select('invoice_code');
$this->db->distinct();
$this->db->where('receipt_code', $receipt_code);
$this->db->where('can_delete !=', 'trash');
$this->db->where('student_id', $student_id);
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
$box_number = $this->db->get_where('settings', array('type' => 'box_number'))->row()->description;
$digital_address = $this->db->get_where('settings', array('type' => 'digital_address'))->row()->description;
$location = $this->db->get_where('settings', array('type' => 'location'))->row()->description;
///$cashier = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
$student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
$class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year))->row()->class_id;
$class = getFullClassName($class_id);

//get total for this invoice code
/*$this->db->select_sum('amount');
$this->db->from('invoice');
$this->db->where('due !=', 0);
$this->db->where('student_id', $student_id);
//$this->db->where('term', $term);
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

$this->db->select_sum('due');
$this->db->where('receipt_code', $receipt_code);
$this->db->where('student_id', $student_id);
$this->db->where('can_delete !=', 'trash');
$balance_owe = $this->db->get('payment')->row()->due;

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
}
*/


//Let's try to do bulk selection
$bulk_inv_code_query = $this->db
                            ->where_in('invoice_code', $invoice_array2)
                            ->where('student_id', $student_id)
                            ->order_by('invoice_code', 'desc')
                            ->where('can_delete !=', 'trash')
                            ->get('invoice');



$issuer_data = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row();
$issuer_name = '';

if (!empty($issuer_data)) {
	$first_name = ucfirst(strtolower(explode(' ', $issuer_data->name)[0]));
	$prefix = ($issuer_data->gender == 'male') ? 'Sir' : (($issuer_data->gender == 'female') ? 'Madam' : '');
	$issuer_name = trim($prefix . ' ' . $first_name);
} else {
	$issuer_name = 'Account Office';
}

// Get invoice discounts for this student
$invoice_discounts = $this->db->where('student_id', $student_id)
    ->where('discount_category', 'invoice')
    ->where('status', 'approved')
    ->get('student_discounts')->result_array();

?>

        <center>
        <page size="A5" id="r_print">
        <div id="main_receipt">

        <center>
        <page size="A5" id="r_print">
        <div id="main_receipt">

            <div class="header-section">
                <!-- School Name - Full Width Top Row -->
                <div style="text-align: center; width: 100%; margin-bottom: 10px;">
                    <div class="school-name" style="font-size: 18px; margin-bottom: 5px;"><?=$system_name;?></div>
                </div>
                
                <!-- Logo, Details, and Receipt Badge Row -->
                <table style="width: 100%;">
                    <tbody>
                        <tr>
                            <td width="15%" style="vertical-align: top;">
                                <img src="<?=base_url('uploads/school_logo.png');?>" class="school-logo">
                            </td>
                            <td width="55%" class="school-info" style="vertical-align: top; text-align: center;">
                                <div class="school-details">
                                    <div><?=$location;?></div>
                                    <?php if(!empty($system_address)): ?><div><?=nl2br($system_address);?></div><?php endif; ?>
                                    <div><?=$box_number . ' | ' . $digital_address;?></div>
                                    <div><?=$system_phone;?></div>
                                    <?php if(!empty($system_mail)): ?><div><?=$system_mail;?></div><?php endif; ?>
                                </div>
                            </td>
                            <td width="30%" align="right" style="vertical-align: top;">
                                <div class="receipt-badge">Official Receipt</div>
                                <table style="margin-top: 8px; width: 100%;">
                                    <tbody>
                                        <tr>
                                            <td align="right" class="receipt-info" style="font-size: 9px;">Year:</td>
                                            <td align="right" class="receipt-info" style="font-size: 9px;"><strong><?=$year;?></strong></td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="receipt-info" style="font-size: 9px;">Term:</td>
                                            <td align="right" class="receipt-info" style="font-size: 9px;"><strong><?=$term;?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bills-section">
                <div class="section-title">Total Bills for the Term</div>
                <table class="bills-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;"><?php echo get_phrase('DESCRIPTION'); ?></th>
                            <th style="text-align: right;"><?php echo get_phrase('AMOUNT'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_amount = 0;
                        $grand_total_amount_paid = 0;
                        $total_balance = 0;
                        $total_original_amount = 0;
                        $total_discount_amount = 0;

                        // Group invoice items by title and sum amounts
                        $grouped_items = array();
                        
                        // First, get discount information like in the professional modal
                        $discount_items_map = array();
                        $discount_query = $this->db->get_where('invoice_discounts', array(
                            'invoice_code' => $invoice_code,
                            'status' => 'approved'
                        ));
                        if($discount_query->num_rows() > 0) {
                            foreach($discount_query->result_array() as $disc) {
                                // Get per-item discount details
                                $disc_items = $this->db->where('discount_id', $disc['discount_id'])->get('invoice_discount_items')->result_array();
                                foreach($disc_items as $ditem) {
                                    $discount_items_map[$ditem['item_title']] = $ditem;
                                }
                            }
                        }
                        
                        foreach ($bulk_inv_code_query->result_array() as $row) {
                            $recentPaidRow = $this->db->get_where('payment', ['receipt_code' => $receipt_code, 'invoice_id' => $row['invoice_id']])->row();
                            $recentPaidAmount = $recentPaidRow ? $recentPaidRow->amount : 0;
                            
                            // Get original billed amount (before any discounts) using the same logic as professional modal
                            $item_discount = isset($discount_items_map[$row['title']]) ? $discount_items_map[$row['title']]['discount_amount'] : 0;
                            $original_amount = $row['amount'] + $item_discount;
                            
                            // Group identical items
                            if(isset($grouped_items[$row['title']])) {
                                $grouped_items[$row['title']]['original_amount'] += $original_amount;
                                $grouped_items[$row['title']]['discount_amount'] += $item_discount;
                                $grouped_items[$row['title']]['final_amount'] += $row['amount'];
                            } else {
                                $grouped_items[$row['title']] = array(
                                    'title' => $row['title'],
                                    'original_amount' => $original_amount,
                                    'discount_amount' => $item_discount,
                                    'final_amount' => $row['amount']
                                );
                            }
                        }

                        // Display grouped items
                        foreach($grouped_items as $item):
                            $total_original_amount += $item['original_amount'];
                            $total_discount_amount += $item['discount_amount'];
                            $total_balance += $item['final_amount'];
                        ?>
           
                        <tr>
                            <td><?php echo $item['title'];?></td>
                            <td class="amount-cell"><?php echo numfmt_format_currency($fmt, $item['original_amount'], $currency); ?></td>
                        </tr>

                        <?php endforeach; ?>

                        <tr class="total-row">
                            <td><strong><?php echo get_phrase('total_amount'); ?></strong></td>
                            <td class="amount-cell"><strong><?php echo numfmt_format_currency($fmt, $total_original_amount, $currency); ?></strong></td>
                        </tr>
                        
                        <?php if($total_discount_amount > 0): ?>
                        <tr class="discount-row">
                            <td>Discount Applied:</td>
                            <td class="amount-cell">-<?=numfmt_format_currency($fmt, $total_discount_amount, $currency);?></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Amount After Discount:</strong></td>
                            <td class="amount-cell"><strong><?php echo numfmt_format_currency($fmt, $total_balance, $currency); ?></strong></td>
                        </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="receipt-details">
                <table class="receipt-table" id="receipt_table">
                    <thead>
                        <tr class="receipt-header">
                            <th style="text-align: left">RECEIPT N<sup><u>o</u></sup>:</th>
                            <th style="text-align: left">#<?=$receipt_code;?></th>
                            <th colspan="2" style="text-align: left">INVOICE N<sup><u>o</u></sup>:</th>
                            <th style="text-align: left">#<?=$invoice_code;?></th>
                            <th style="text-align: right">PAID ON:</th>
                            <th style="text-align: right" colspan="2"><?=date('M j, Y g:i A', $date);?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td align="left">Received from:</td>
                            <td align="left" colspan="5" class="student-name"><?=strtoupper(strtolower($student_name));?></td>
                            <td align="right" colspan="2" style="text-align: right;">Class: <?=$class;?></td>
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

                        <tr>
                            <td align="left">The Sum of:</td>
                            <td align="left" colspan="7" class="amount-words"><?=$amountInWords;?></td>
                        </tr>

                        <tr>
                            <td align="left">Being:</td>
                            <td align="left" colspan="7">
                                <?php
                                echo $paymentStatus . ' for Fees.';
                                ?>
                            </td>
                        </tr>

                        <?php
                            /*We display this section only if transaction is cheque or momo*/
                            if($method == 'Cheque' || $method == 'Momo'):
                        ?>
                        <tr>
                            <td align="left"><?=$method == 'Cheque' ? 'Cheque N<sup><u>o</u></sup>' : 'Momo Transaction ID'; ?>:</td>
                            <td align="left" colspan="7">
                                <?=$method == 'Cheque' ? $bank_name.' | '. $cheque_number : $transaction_id; ?>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td align="left">Amount Paid:</td>
                            <td align="left" colspan="4"><strong><?=numfmt_format_currency($fmt, $total_amount_paid, $currency);?></strong></td>
                            <?php if ($balance_owe < 0): ?>
                            <td align="right" colspan="3" style="text-align: right; color: #10b981; font-weight: bold;">Credit Balance: <strong><?=numfmt_format_currency($fmt, $balance_owe, $currency);?></strong></td>
                            <?php else: ?>
                            <td align="right" colspan="3" style="text-align: right;">Balance: <strong><?=numfmt_format_currency($fmt, $balance_owe, $currency);?></strong></td>
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
                        <tr style="background: linear-gradient(135deg, #d4edda, #c3e6cb); border-top: 2px solid #28a745;">
                            <td colspan="8" align="center" style="padding: 12px;">
                                <div style="color: #155724; font-weight: bold; font-size: 14px;">
                                    <i class="fa fa-gift" style="font-size: 16px; margin-right: 5px;"></i>
                                    CREDIT CREATED: <?=numfmt_format_currency($fmt, $credit_check->credit_amount, $currency);?>
                                </div>
                                <div style="color: #155724; font-size: 11px; margin-top: 3px;">
                                    This credit will be automatically applied to reduce future bills
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endif;
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="signature-section">
                <table class="receipt-table">
                    <tr>
                        <td align="right" colspan="6">Bursar's Signature:</td>
                        <td align="right" colspan="2" class="signature-line"></td>
                    </tr>
                    <tr><td colspan="8" style="height: 8px;"></td></tr>
                    <tr>
                        <td align="right" colspan="6">Issued By:</td>
                        <td align="right" colspan="2" class="issuer-name"><?=$issuer_name;?></td>
                    </tr>
                </table>
            </div>

            <!-- Thank You Footer -->
            <div class="thank-you-footer" style="position: absolute; bottom: 15mm; left: 15mm; right: 15mm; text-align: center; padding-top: 15px; border-top: 1px solid #bdc3c7; background: white;">
                <div style="font-size: 12px; font-weight: bold; color: #2c3e50; margin-bottom: 5px;">
                    Thank You For Choosing <?=ucwords(strtolower($system_name));?>
                </div>
                <div style="font-size: 9px; color: #7f8c8d; font-style: italic;">
                    This is a computer-generated receipt and does not require a physical signature.
                </div>

            </div>

            <!-- <center>
                <div class="row">
                    <p style="color: #6f686c; margin-top: 20px">
                        Software Developed By: teamLightworldtech - 0243618186 | 0247612799
                    </p>
                </div>
            </center> -->
        </div>
     </page>
     
        <div class="print-buttons">
            <button onclick="PrintElem('#r_print')" class="btn btn-print">Print Receipt</button>
        </div>
        </center>

<script type="text/javascript">
function PrintElem(elem) {
    var content = $(elem).html();
    var printWindow = window.open('', 'Receipt', 'height=600,width=800');
    printWindow.document.write('<!doctype html><html><head><title>Receipt</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('#watermark {position: absolute;font-size: 28px;transform: rotate(-35deg);margin-top: 70px;color: #a09e9f;}');
    printWindow.document.write('body {font-size: 14px;}');
    printWindow.document.write('table {width: 100%;border-collapse: collapse;}');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}
</script>