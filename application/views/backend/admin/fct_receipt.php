<!doctype html>
<html>
	<head>
		<title>Payment Receipt</title>
        <style type="text/css">

             @media Print
             {
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
                    padding: 5px 0px;"
                }
            }
        </style>
	</head>
	<body>
		<?php
			//currency
        	$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        	$fmt = new NumberFormatter('ms_MS.utf8', NumberFormatter::DECIMAL);

            $year = get_settings('running_year'); 
            $term = get_settings('running_term');

		    $system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
            $system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
            $system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
            $system_slogan = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;
            ///$cashier = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
            $student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
            $class_id        = $this->db->get_where('enroll', array('student_id' => $student_id, 'mute' => '0', 'year' => $year))->row()->class_id;
            $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
            $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

            $this->db->where('receipt_code', $receipt_code);
            $this->db->where('student_id', $student_id);
            $this->db->where('can_delete !=', 'trash');
           // $this->db->where('term', $term);
           // $this->db->where('year', $year);
            $balance_owe = $this->db->get('payment')->row()->due;     
            
            //add section A or B if the class has more than one section
            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
            $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
            $sec_name = '';
            if($class_has_more_sections > 1) {
                $sec_name = $section_name;
            }

            $class = '';
            if($class_name == 'CRECHE') {
                $class = ucwords(strtolower($class_name));
            } else {
                $class = ucwords(strtolower($class_name)). ' '. $class_name_numeric.$sec_name;
            }


            //who issued the receipt
            $issuer_id = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->issuer_id;

            $issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name;
            $account_type = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->account_type;

            if(!empty($issuer_name) && !empty($account_type)) {
                $issuer_name = $issuer_name;
            } else {
                $issuer_name =  'Not Available';
            }

        ?>
        
        <center>
        <page size="A6" id="r_print">
        <div id="main_receipt">
            <table style="border: 1px solid #d0cccc; padding: 5px;">
            <tr>
            <td align="center">
            <div><img src="<?= base_url('uploads/school_logo.png'); ?>" style="max-height : 120px;"></div><br>
            <div><strong><?= $system_name; ?></strong></div>
            <div><strong><?= $system_phone .' | '. $system_mail; ?></strong></div>

            <div class="row" style="padding: 8px;" >
                <center><strong id="official" style="background-color: #000; width: 200px; padding: 6px; border-radius: 9px; color: #fff; font-size: 23px">Official Receipt</strong></center>
            </div>

            <div>
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left">Received From:</th>
                            <th align="right" colspan="2"><?= ucwords(strtolower($student_name)); ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Year | Term:</th>
                            <th align="right" colspan="2"><?= explode('-', $year)[1]. ' | '. $term; ?></th>
                        </tr> 
                        <tr>
                            <th style="text-align: left">Class/Form:</th>
                            <th align="right" colspan="2"><?= $class; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Paid On:</th>
                            <th align="right" colspan="2"><?= date('d/m/Y H:i:s', $date); ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Receipt No.:</th>
                            <th align="right" colspan="2"><?= $receipt_code; ?></th>
                        </tr>
                        <!--<tr>
                            <th style="text-align: left">Invoice No.:</th>
                            <th align="right" colspan="2"><?= $invoice_code; ?></th>
                        </tr> -->
                        <tr>
                            <th style="text-align: left">Currency:</th>
                            <th style="text-align: right" colspan="2"><?= $currency; ?></th>
                        </tr>
                        <tr>
                          <th style="text-align: left">Issued On:</th>
                          <th align="right" colspan="2"><?= date('d/m/Y H:i:s') ?></th>
                          <th></th>
                          <th align="right"></th>
                        </tr>
                    </thead>
                </table>
            </div><br>
            <table>
                <thead>
                    <tr >
                        <th width="40" align="left">S/N3</th>
                        <th width="180" align="left">ITEM</th>
                        <th></th>
                        <th width="120" align="right">PAID</th>
                    </tr>
                    <tr><th colspan="4"><hr></th></tr>
                </thead>
                <tbody>

                	<?php 
                		 $receipt_querry = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->result_array();

				        $i = 1;
				        foreach($receipt_querry as $row) {
				             
				          echo  '
				                <tr>
				                    <td>'.$i.'</td>
				                    <td align="left" colspan="2" style="font-size: 12px">'. $row['title'].'</td>                    
				                    <td align="right">'.number_format($row['amount'], 2, '.', ',').'</td>
				                </tr>
				            ';

				            $i++;

				        }

                	?>
                	<tr></tr>
                    <tr></tr>
                    <tr></tr>
                    <tr><td colspan="4"><hr></td></tr>
                    <tr>
                        <td align="right" colspan="3">Sub-total:</td>
                        <td align="right"><?= number_format($total_amount_paid, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Tax:</td>
                        <td align="right"><?= number_format(0, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Net Amount Payable:</td>
                        <td align="right" style="font-weight: bolder;"><?= number_format($total_amount_paid, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                    	<td align="right" colspan="3">Balance Owe:</td>
                        <td align="right" style="font-weight: bolder;"><?= number_format($balance_owe, 2, '.', ','); ?></td>
                    </tr>

                    <tr><td><br></td></tr>
                    <tr>
                        <td colspan="2">
                            <strong>Cashier: </strong><br><?= ucwords(strtolower($issuer_name)); ?>
                            <hr>
                        </td>
                        <?php 
                            $method = $this->db->get_where('payment', array('receipt_code' => $receipt_code))->row()->method;
                            if ($method == 1) {
                                    $m = get_phrase('cash');
                                if ($method == 2)
                                    $m = get_phrase('cheque');
                                if ($method == 3)
                                    echo get_phrase('card');
                                if ($method == 4)
                                    $m = 'Mobile Money';
                            }
                        ?>
                        <td colspan="2" align="right"><strong>Method: </strong><br><?= $m; ?> <hr></td>
                    </tr>
                    <tr><td colspan="4" align="center">
                        <p><?= $system_slogan."!!!"; ?></p>
                        <p><strong>Thank You</strong></p>
                    </td></tr>
                </tbody>
            </table>
        </td>
        </tr>
        </table>
        </div>
     </page><br>
        <button onclick="PrintElem('#r_print')" style="border: 1 solid #0cc; padding: 10px; cursor: pointer; background-color: green; color: #fff;">Print</button>
        <button onclick="load_back()" style="position:relative; border: 1 solid #0cc; cursor: pointer; padding: 10px; background-color: red; color: #fff;">Back</button>
        </center>
    

<script type="text/javascript">
	function load_back() {
		window.location.href = '<?= site_url($this->session->userdata('login_type').'/income'); ?>';
	}
</script>
    
    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
	</body>
</html>

<script type="text/javascript">
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'Receipt', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body style="font-sie: 14px">');
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