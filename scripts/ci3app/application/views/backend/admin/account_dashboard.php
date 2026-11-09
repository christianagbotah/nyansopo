<?php
//currency
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    $un_year = '';
    $un_term = '';
    $un_sem = '';

if($search == 'search') {

  $year;
  $term;
  $sem;

        $un_year = $year;
        $un_term = $term;
        $un_sem = $sem;
        $timestamp = $date;
        $full_date = 'for Year '.$un_year.' | Term '.$un_term; //As at '. date('l M d, Y', $timestamp);
        $full_date2 = 'for Year '.$un_year.' | Term '.$un_term; //'As at '. date('l M d, Y', $timestamp);


$querry_arr = array('year' => $year, 'due >' => '0');
$this->db->select('invoice_code');
$this->db->distinct();
$this->db->from('invoice');
$this->db->where('can_delete !=', 'trash');
$this->db->where($querry_arr);
$this->db->where('term', $term);
$this->db->where('mute', '0');

$this->db->order_by('invoice_code', 'desc');
$unpaid_invoices = $this->db->get()->num_rows();

$this->db->select_sum('due');
$this->db->where('year', $year);
$this->db->where('due >', '0');
$this->db->where('term', $term);
$this->db->where('mute', '0');
$this->db->where('can_delete !=', 'trash');
$unpaid_invoices_amount = $this->db->get('invoice')->result_array();
foreach($unpaid_invoices_amount as $amount)
    $total_unpaid_invoice_amount = $amount['due']; 


$payment_arr = array('year' => $year, 'payment_type' => 'income');

$this->db->select('receipt_code');
$this->db->distinct();
$this->db->from('payment');
$this->db->where($payment_arr);
$this->db->where('term', $term);
$this->db->where('can_delete !=', 'trash');
$this->db->order_by('receipt_code', 'desc');
$payment_rows = $this->db->get()->num_rows();

$total_income = 0;

$this->db->where($payment_arr);
$this->db->where('term', $term);
$this->db->where('can_delete !=', 'trash');
$payments = $this->db->get('payment')->result_array();
foreach($payments as $row) 
    $total_income += number_format($row['amount']);

$total_expense = 0;
$this->db->where('year', $year);
$this->db->where('payment_type', 'expense');
$this->db->where('term', $term);
$this->db->where('can_delete !=', 'trash');
$payments = $this->db->get('payment')->result_array();
foreach($payments as $row) 
    $total_expense += $row['amount'];

    $unpaid_invoices = $unpaid_invoices;
    $total_income = numfmt_format_currency($fmt, $total_income, $currency);
    $total_expense = numfmt_format_currency($fmt, $total_expense, $currency);;  
    $total_unpaid_invoice_amount = numfmt_format_currency($fmt, $total_unpaid_invoice_amount, $currency); 


    //feeding fee
    $this->db->select('timestamp');
    $this->db->distinct();
    $this->db->where('timestamp <=', $timestamp);
    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $this->db->order_by('timestamp', 'desc');
    $this->db->limit(1);
    $last_timestamp = $this->db->get('feeding_fee')->row()->timestamp;


    $this->db->where('timestamp', $last_timestamp);
    $this->db->where('mute', '0');
    $feeding_owe_now = $this->db->get('feeding_fee')->result_array();

    $total_feeding_owe = 0;
    $total_feeding_paid = 0;

    $total_classes_owe = 0;
    $total_classes_paid = 0;

    foreach($feeding_owe_now as $feeding) {
        if($feeding['due'] > 0) {
            $total_feeding_owe = $total_feeding_owe + $feeding['due'];
        }

        if($feeding['cdue'] > 0) {
            $total_classes_owe = $total_classes_owe + $feeding['cdue'];
        }
        
        
    }

    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $this->db->where('mute', '0');
    $feeding_paid_array = $this->db->get('feeding_fee')->result_array();

    foreach($feeding_paid_array as $feeding2) {

        $total_feeding_paid = $total_feeding_paid + $feeding2['feeding_paid'];
        $total_classes_paid = $total_classes_paid + $feeding2['classes_paid'];
    }

//fare 
    // Note: transport_fare table uses payment_date (date) and created_at (timestamp), not timestamp
    // Get the latest payment_date on or before the selected date
    $date_str = date('Y-m-d', $timestamp);
    $this->db->select('payment_date');
    $this->db->distinct();
    $this->db->where('payment_date <=', $date_str);
    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $this->db->order_by('payment_date', 'desc');
    $this->db->limit(1);
    $last_payment_result = $this->db->get('transport_fare');
    $last_payment_date = $last_payment_result->num_rows() > 0 ? $last_payment_result->row()->payment_date : null;


    // Note: New transport_fare schema doesn't track 'due' - only tracks payments
    // If you need to track amounts owed, use transport route fare minus payments
    $this->db->where('payment_date', $last_payment_date);
    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $fare_owe_now = $this->db->get('transport_fare')->result_array();

    $total_fare_owe = 0;
    $total_fare_paid = 0;
    foreach($fare_owe_now as $fare) {
        // Note: 'due' field doesn't exist in new schema - calculating based on fare charges
        // This would need to be calculated separately if tracking is needed
    }


    // Note: transport_fare table doesn't have 'mute' field in new schema
    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $fare_paid_array = $this->db->get('transport_fare')->result_array();
    foreach($fare_paid_array as $fare2) {
        $total_fare_paid = $total_fare_paid + $fare2['amount_paid'];
    }
}else{

    $un_year = $running_year;
    $un_term = $running_term;
    $un_sem = $running_sem;
    $timestamp = strtotime(date('M d, Y'));

    //let's select the current date in the tables
    $feeding_timestamp = $this->db
                                ->select('timestamp')
                                ->distinct()
                                ->order_by('timestamp', 'desc')
                                ->limit(1)
                                ->get('feeding_fee')->row()->timestamp;

    // Note: transport_fare uses payment_date instead of timestamp
    $transport_payment_date = $this->db
                                ->select('payment_date')
                                ->distinct()
                                ->order_by('payment_date', 'desc')
                                ->limit(1)
                                ->get('transport_fare');
    $transport_timestamp = $transport_payment_date->num_rows() > 0 && $transport_payment_date->row()->payment_date 
                            ? strtotime($transport_payment_date->row()->payment_date) 
                            : null;

    $full_date = 'received this term';
    $full_date2 = 'for this term';

    if($feeding_timestamp == NULL) {
        $feeding_timestamp = strtotime(date('d-m-Y'));
    }

    if($transport_timestamp == NULL) {
        $transport_timestamp = strtotime(date('d-m-Y'));
    }

    


    $querry_arr = array('year' => $running_year, 'due >' => '0');
    $this->db->select('invoice_code');
    $this->db->distinct();
    $this->db->from('invoice');
    $this->db->where($querry_arr);
    $this->db->where('can_delete !=', 'trash');
    $this->db->where('term', $running_term);
    $this->db->where('mute', '0');
    $this->db->order_by('invoice_code', 'desc');
    $unpaid_invoices = $this->db->get()->num_rows();



    $this->db->select_sum('due');
    $this->db->where('due >', '0');
    $this->db->where('mute', '0');
    $this->db->where('can_delete !=', 'trash');
    $unpaid_invoices_amount = $this->db->get_where('invoice', array('year' => $running_year, 'term' => $running_term))->result_array();
    foreach($unpaid_invoices_amount as $amount)
        $total_unpaid_invoice_amount = $amount['due']; 

    $payment_arr = array('year' => $running_year, 'payment_type' => 'income');
    $this->db->select('receipt_code');
    $this->db->distinct();
    $this->db->from('payment');
    $this->db->where($payment_arr);
    $this->db->where('term', $running_term);
    $this->db->order_by('receipt_code', 'desc');
    $payment_rows = $this->db->get()->num_rows();


    $total_income = 0;
    $this->db->where('payment_type', 'income');
    $this->db->where('year', $running_year);
    $this->db->where('term', $running_term);
    $this->db->where('can_delete !=', 'trash');
    $payments = $this->db->get('payment')->result_array();
    foreach($payments as $row) 
        $total_income += number_format($row['amount']);

    $total_expense = 0;
    $this->db->where('payment_type', 'expense');
    $this->db->where('year', $running_year);
    $this->db->where('term', $running_term);
    $this->db->where('can_delete !=', 'trash');
    $payments = $this->db->get('payment')->result_array();
    foreach($payments as $row) 
        $total_expense += $row['amount'];

        $unpaid_invoices = $unpaid_invoices;
        $total_income = numfmt_format_currency($fmt, $total_income, $currency);
        $total_expense = numfmt_format_currency($fmt, $total_expense, $currency);;  
        $total_unpaid_invoice_amount = numfmt_format_currency($fmt, $total_unpaid_invoice_amount, $currency); 


    //feeding fee

    $this->db->where('timestamp', $feeding_timestamp);
    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $this->db->where('mute', '0');
    $feeding_owe_now = $this->db->get('feeding_fee')->result_array();

    $total_feeding_owe = 0;
    $total_feeding_paid = 0;

    $total_classes_owe = 0;
    $total_classes_paid = 0;

    foreach($feeding_owe_now as $feeding) {

        if($feeding['due'] > 0) {
            $total_feeding_owe = $total_feeding_owe + $feeding['due'];
        }

        if($feeding['cdue'] > 0) {
            $total_classes_owe = $total_classes_owe + $feeding['cdue'];
        }
        
        
    }


    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $this->db->where('mute', '0');
    $feeding_paid_array = $this->db->get('feeding_fee')->result_array();

    foreach($feeding_paid_array as $feeding2) {

        $total_feeding_paid = $total_feeding_paid + $feeding2['feeding_paid'];
        $total_classes_paid = $total_classes_paid + $feeding2['classes_paid'];
    }

//fare 
    // Note: Using payment_date instead of timestamp, convert timestamp back to date
    $transport_payment_date = date('Y-m-d', $transport_timestamp);
    $this->db->where('payment_date', $transport_payment_date);
    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $fare_owe_now = $this->db->get('transport_fare')->result_array();

    $total_fare_owe = 0;
    $total_fare_paid = 0;
    foreach($fare_owe_now as $fare) {
        // Note: 'due' field doesn't exist in new schema - only tracks payments
        // Amounts owed would need to be calculated separately based on route fares
    }


    // Note: transport_fare table doesn't have 'mute' field in new schema
    $this->db->where('year', $un_year);
    $this->db->where('term', $un_term);
    $fare_paid_array = $this->db->get('transport_fare')->result_array();
    foreach($fare_paid_array as $fare2) {
        $total_fare_paid = $total_fare_paid + $fare2['amount_paid'];
    } 
}




    //summary
    $total_feeding_owe = numfmt_format_currency($fmt, $total_feeding_owe, $currency);
    $total_classes_owe = numfmt_format_currency($fmt, $total_classes_owe, $currency);
    $total_fare_owe = numfmt_format_currency($fmt, $total_fare_owe, $currency);

    $total_feeding_paid = numfmt_format_currency($fmt, $total_feeding_paid, $currency);
    $total_classes_paid = numfmt_format_currency($fmt, $total_classes_paid, $currency);
    $total_fare_paid = numfmt_format_currency($fmt, $total_fare_paid, $currency);

    
    $timestamp = date('l M d, Y', $timestamp);


?>


<div class="row">
    
    <div class="col-md-4">
      <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_unpaid_invoices/'.$un_term .'/'. $un_year.'/'. $un_sem);?>', 'modal_unpaid_invoices');">
        <div class="tile-stats tile-red">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-credit-card" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0" 
                    data-postfix="" data-duration="1500" data-delay="0"> <?php echo $total_unpaid_invoice_amount?$total_unpaid_invoice_amount:0; ?></div>

            <sub style="font-weight: bold; color: #ffffff; font-size: 15px; margin-top: -69px;" class="pull-right"><?php echo 'Quantity'; ?> <label class="label label-info"><?php echo $unpaid_invoices?$unpaid_invoices:0; ?></label></sub>
            
            <h3><?php echo get_phrase('unpaid_invoices_<small style="color: #fff; font-size: bold;">').$full_date2; ?></small></h3>
        </div>
      </a>
        
    </div>

    <div class="col-md-4">
    
        <div class="tile-stats tile-green">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-money" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="800" data-delay="0"><?php echo $total_income?$total_income:0; ?></div>

            <sub style="font-weight: bold; color: #ffffff; font-size: 15px; margin-top: -69px;" class="pull-right"><?php echo 'No. of Receipts'; ?> <label class="label label-danger"><?php echo $payment_rows?$payment_rows:0; ?></label></sub>
            
            <h3><?php echo get_phrase('total_income_<small style="color: #fff; font-size: bold;">').$full_date; ?></small></h3>
        </div>
        
    </div>

    <div class="col-md-4">
    
        <div class="tile-stats tile-aqua cursor-pointer" onclick="navigation('<?php echo site_url('admin/expense'); ?>')">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-tags" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="500" data-delay="0"><?php echo $total_expense?$total_expense:0; ?></div>
            
            <h3><?php echo get_phrase('total_expenses');?></h3>
        </div>
        
    </div>
    
</div>

<hr>

<!-- FEEDING, CLASSES FEES AND TRANSPORTATION FARE-->
<div class="row">
    <a href="#" onclick="navigation('<?php echo site_url($this->session->userdata('login_type').'/cft_student_receipt'); ?>')">
    <div class="col-md-3">
    
        <div class="tile-stats tile-blue">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="500" data-delay="0"><?php echo $total_feeding_paid?$total_feeding_paid:0; ?></div>
            
            <h3><?php echo get_phrase('total_feeding_fee');?></h3>
            <p>Year: <?= explode('-', $un_year)[1]; ?> | Term: <?= $un_term; ?></p>
        </div>
        
    </div>
    </a>

    <a href="#" onclick="navigation('<?php echo site_url($this->session->userdata('login_type').'/cft_student_receipt'); ?>')">
    <div class="col-md-3">
    
        <div class="tile-stats tile-green">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-money" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="500" data-delay="0"><?php echo $total_classes_paid?$total_classes_paid:0; ?></div>
            
            <h3><?php echo get_phrase('total_classes_fee');?></h3>
            <p>Year: <?= explode('-', $un_year)[1]; ?> | Term: <?= $un_term; ?></p>
        </div>
        
    </div>
    </a>

    <a href="#" onclick="navigation('<?php echo site_url($this->session->userdata('login_type').'/cft_student_receipt'); ?>')">
    <div class="col-md-3">
    
        <div class="tile-stats tile-black">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-bus" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="500" data-delay="0"><?php echo $total_fare_paid?$total_fare_paid:0; ?></div>
            
            <h3><?php echo get_phrase('total_transport_fare');?></h3>
            <p>Year: <?= explode('-', $un_year)[1]; ?> | Term: <?= $un_term; ?></p>
        </div>
        
    </div>
    </a>

    <a href="<?= site_url($this->session->userdata('login_type').'/fct_debtors/'.strtotime($timestamp)); ?>" target="_blank" title="Debtors List">
    <div class="col-md-3">
        <div class="tile-stats tile-red">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-line-chart" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 14px;"><?php echo 'UNPAID BALANCES'; ?></sub> <div style="color: #fff; font-size: 23px; font-weight: bolder;" data-start="0"
            data-postfix="" data-duration="500" data-delay="0"><?php echo $total_feeding_owe?$total_feeding_owe:0; ?> <small style="font-size: 12px;">(Feeding)</small></div>

            <div style="color: #fff; font-size: 23px; font-weight: bolder;"   data-start="0"
            data-postfix="" data-duration="500" data-delay="0"><?php echo $total_classes_owe?$total_classes_owe:0; ?> <small style="font-size: 12px;">(Classes)</small></div>

            <div style="color: #fff; font-size: 23px; font-weight: bolder;"   data-start="0"
            data-postfix="" data-duration="500" data-delay="0"><?php echo $total_fare_owe?$total_fare_owe:0; ?> <small style="font-size: 12px;">(Transport)</small></div>      
        </div>
    </div>
    </a>

    
</div>



  












