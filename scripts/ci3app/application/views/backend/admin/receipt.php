<!doctype html>
<html>
    <head>
        <title>Payment Receipt</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.3;
                color: #000;
                background: #f5f5f5;
                margin: 0;
                padding: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            
            .page-wrapper {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;
            }
            
            .receipt-container {
                max-width: 80mm;
                margin: 0 auto;
                background: #fff;
                padding: 3mm;
                border: 1px solid #000;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            
            .header {
                text-align: center;
                margin-bottom: 8px;
                border-bottom: 1px solid #000;
                padding-bottom: 5px;
            }
            
            .logo {
                max-height: 35px;
                width: auto;
                margin-bottom: 3px;
            }
            
            .school-name {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 2px;
                text-transform: uppercase;
            }
            
            .school-contact {
                font-size: 9px;
                margin-bottom: 2px;
            }
            
            .receipt-badge {
                background: #000;
                color: #fff;
                padding: 3px 10px;
                font-size: 10px;
                font-weight: bold;
                margin: 3px 0;
                display: inline-block;
            }
            
            .receipt-info {
                margin: 5px 0;
                font-size: 10px;
            }
            
            .info-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 1px;
                font-size: 10px;
            }
            
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 5px 0;
                font-size: 9px;
            }
            
            .items-table th {
                border-bottom: 1px solid #000;
                padding: 2px 1px;
                text-align: left;
                font-weight: bold;
            }
            
            .items-table th.amount {
                text-align: right;
            }
            
            .items-table td {
                padding: 1px;
                border-bottom: 1px dotted #ccc;
            }
            
            .amount {
                text-align: right;
                font-weight: bold;
            }
            
            .total-row {
                border-top: 1px solid #000;
                font-weight: bold;
                background: #f0f0f0;
            }
            
            .discount-row {
                background: #e8f5e8;
                font-weight: bold;
            }
            
            .footer {
                margin-top: 8px;
                border-top: 1px solid #000;
                padding-top: 5px;
                text-align: center;
                font-size: 9px;
            }
            
            .signature-section {
                margin: 5px 0;
                font-size: 9px;
            }
            
            .signature-line {
                border-bottom: 1px dotted #000;
                height: 15px;
                margin: 2px 0;
            }
            
            .print-buttons {
                text-align: center;
                margin-top: 15px;
                width: 100%;
            }
            
            .btn {
                padding: 8px 15px;
                margin: 0 5px;
                border: none;
                cursor: pointer;
                font-size: 11px;
                border-radius: 3px;
            }
            
            .btn-print {
                background: #28a745;
                color: white;
            }
            
            .btn-back {
                background: #dc3545;
                color: white;
            }
            
            #watermark {
                position: absolute;
                font-size: 20px;
                transform: rotate(-35deg);
                color: #ddd;
                z-index: -1;
                top: 50%;
                left: 50%;
                transform-origin: center;
            }
            
            /* Thermal Printer Styles */
            @media print {
                body {
                    margin: 0;
                    padding: 5mm;
                    font-size: 10px;
                    background: white;
                    display: block;
                    min-height: auto;
                }
                
                .page-wrapper {
                    display: block;
                }
                
                .receipt-container {
                    max-width: 80mm;
                    width: 80mm;
                    margin: 0 auto;
                    padding: 3mm;
                    border: none;
                    box-shadow: none;
                }
                
                .logo {
                    max-height: 25px;
                }
                
                .school-name {
                    font-size: 11px;
                }
                
                .school-contact {
                    font-size: 8px;
                }
                
                .receipt-badge {
                    font-size: 9px;
                    padding: 2px 8px;
                }
                
                .info-row {
                    font-size: 9px;
                }
                
                .items-table {
                    font-size: 8px;
                }
                
                .items-table th,
                .items-table td {
                    padding: 1px;
                }
                
                .footer {
                    font-size: 8px;
                }
                
                .signature-section {
                    font-size: 8px;
                }
                
                .print-buttons {
                    display: none !important;
                }
                
                #watermark {
                    font-size: 15px;
                }
            }
            
            /* A4/A5 Paper Styles */
            @media print and (min-width: 148mm) {
                body {
                    font-size: 11px;
                    padding: 15mm;
                    display: flex;
                    justify-content: center;
                    align-items: flex-start;
                    min-height: 100vh;
                    background: white;
                }
                
                .page-wrapper {
                    display: block;
                    width: 100%;
                }
                
                .receipt-container {
                    max-width: 130mm;
                    width: 130mm;
                    padding: 8mm;
                    margin: 0 auto;
                    box-shadow: none;
                    border: 1px solid #ddd;
                }
                
                .logo {
                    max-height: 40px;
                }
                
                .school-name {
                    font-size: 13px;
                }
                
                .school-contact {
                    font-size: 9px;
                }
                
                .receipt-badge {
                    font-size: 10px;
                }
                
                .info-row {
                    font-size: 10px;
                }
                
                .items-table {
                    font-size: 9px;
                }
                
                .footer {
                    font-size: 9px;
                }
                
                .signature-section {
                    font-size: 9px;
                }
            }
        </style>
    </head>
    <body>
        <?php
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
        $box_number = $this->db->get_where('settings', array('type' => 'box_number'))->row()->description;
        $digital_address = $this->db->get_where('settings', array('type' => 'digital_address'))->row()->description;
        $location = $this->db->get_where('settings', array('type' => 'location'))->row()->description;
        $student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;

        $class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term))->last_row()->class_id;
        
        // Get payment date
        $payment_row = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row();

        $class_id = $payment_row->class_id;
        $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;

        $date = is_numeric($payment_row->timestamp) ? $payment_row->timestamp : strtotime($payment_row->timestamp);
        
        // Get total amount paid
        $this->db->select_sum('amount');
        $this->db->where('receipt_code', $receipt_code);
        $this->db->where('student_id', $student_id);
        $this->db->where('can_delete !=', 'trash');
        $total_amount_paid = $this->db->get('payment')->row()->amount;
        
        // Get balance owed
        $this->db->select_sum('due');
        $this->db->where('receipt_code', $receipt_code);
        $this->db->where('student_id', $student_id);
        $this->db->where('can_delete !=', 'trash');
        $balance_owe = $this->db->get('payment')->row()->due;
        
        // Get issuer info
        $issuer_id = $payment_row->issuer_id;
        $issuer_data = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row();
        
        $issuer_name = '';
        if (!empty($issuer_data)) {
            $first_name = ucfirst(strtolower(explode(' ', $issuer_data->name)[0]));
            $prefix = ($issuer_data->gender == 'male') ? 'Sir' : (($issuer_data->gender == 'female') ? 'Madam' : '');
            $issuer_name = trim($prefix . ' ' . $first_name);
        } else {
            $issuer_name = 'Account Office';
        }
        ?>
        
        <div class="page-wrapper">
            <div class="receipt-container">
            <div class="header">
                <img src="<?=base_url('uploads/school_logo.png');?>" class="logo" alt="School Logo">
                <div class="school-name"><?=$system_name;?></div>
                <div class="school-contact">
                    <?php if(!empty($location)): ?><div><?=$location;?></div><?php endif; ?>
                    <?php if(!empty($system_address)): ?><div><?=nl2br($system_address);?></div><?php endif; ?>
                    <?php if(!empty($box_number) || !empty($digital_address)): ?>
                        <div><?=trim($box_number . ' | ' . $digital_address, ' |');?></div>
                    <?php endif; ?>
                    <div><?=$system_phone;?></div>
                    <?php if(!empty($system_mail)): ?><div><?=$system_mail;?></div><?php endif; ?>
                </div>
                <div class="receipt-badge">OFFICIAL RECEIPT</div>
            </div>
            
            <div class="receipt-info">
                <div class="info-row">
                    <span><strong>Receipt No:</strong></span>
                    <span><?=$receipt_code;?></span>
                </div>
                <div class="info-row">
                    <span><strong>Invoice No:</strong></span>
                    <span><?=$invoice_code;?></span>
                </div>
                <div class="info-row">
                    <span><strong>Student:</strong></span>
                    <span><?=strtoupper($student_name);?></span>
                </div>
                <div class="info-row">
                    <span><strong>Class:</strong></span>
                    <span><?=$class;?></span>
                </div>
                <div class="info-row">
                    <span><strong>Year | Term:</strong></span>
                    <span><?=$is_multi_invoice ? $year . ' | ' . $term : explode('-', $year)[1] . ' | ' . $term;?></span>
                </div>
                <div class="info-row">
                    <span><strong>Paid On:</strong></span>
                    <span><?=date('M j, Y g:i A', $date);?></span>
                </div>
                <?php
                // Get discount details
                $discount_total = 0;
                if($is_multi_invoice) {
                    foreach($invoice_array2 as $inv_code) {
                        $discounts = $this->db->where('invoice_code', $inv_code)
                                              ->where('status', 'approved')
                                              ->where('discount_category', 'invoice')
                                              ->get('invoice_discounts')->result_array();
                        foreach($discounts as $disc) {
                            $discount_total += $disc['discount_amount'];
                        }
                    }
                } else {
                    $discounts = $this->db->where('invoice_code', $invoice_code)
                                          ->where('status', 'approved')
                                          ->where('discount_category', 'invoice')
                                          ->get('invoice_discounts')->result_array();
                    foreach($discounts as $disc) {
                        $discount_total += $disc['discount_amount'];
                    }
                }
                
                if($discount_total > 0):
                ?>
                <div class="info-row" style="color: #27ae60; font-weight: bold;">
                    <span><strong>Discount Applied:</strong></span>
                    <span>-<?=number_format($discount_total, 2);?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="5%">S/N</th>
                        <th width="65%">ITEM</th>
                        <th width="30%" class="amount">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $receipt_querry = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->result_array();
                    
                    $i = 1;
                    $total_paid = 0;
                    foreach ($receipt_querry as $row) {
                        $total_paid += $row['amount'];
                        echo '<tr>
                                <td>' . $i . '</td>
                                <td>' . $row['title'] . '</td>
                                <td class="amount">' . number_format($row['amount'], 2) . '</td>
                              </tr>';
                        $i++;
                    }
                    
                    ?>
                    
                    <tr class="total-row">
                        <td colspan="2"><strong>AMOUNT PAID:</strong></td>
                        <td class="amount"><strong><?=number_format($total_amount_paid, 2);?></strong></td>
                    </tr>
                    
                    <?php if ($balance_owe < 0): ?>
                    <tr style="background: #e8f5e8;">
                        <td colspan="2" style="color: #10b981; font-weight: bold;">Credit Balance:</td>
                        <td class="amount" style="color: #10b981; font-weight: bold;"><?=number_format($balance_owe, 2);?></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="2">Balance Due:</td>
                        <td class="amount"><?=number_format($balance_owe, 2);?></td>
                    </tr>
                    <?php endif; ?>
                    
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
                    <tr style="border-top: 2px solid #28a745;">
                        <td colspan="3" style="padding: 0;"></td>
                    </tr>
                    <tr style="background: linear-gradient(135deg, #d4edda, #c3e6cb);">
                        <td colspan="3" style="padding: 8px; text-align: center;">
                            <div style="color: #155724; font-weight: bold; font-size: 9px;">
                                <i class="fa fa-gift"></i> CREDIT CREATED: GH₵ <?=number_format($credit_check->credit_amount, 2)?>
                            </div>
                            <div style="color: #155724; font-size: 7px; margin-top: 2px;">
                                Will be applied to future bills
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endif;
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="signature-section">
                <div class="info-row">
                    <span><strong>Payment Method:</strong></span>
                    <span><?=ucwords($this->db->get_where('payment', array('receipt_code' => $receipt_code))->row()->payment_method);?></span>
                </div>
                <div class="info-row">
                    <span><strong>Cashier:</strong></span>
                    <span><?=$issuer_name;?></span>
                </div>
                <div class="signature-line"></div>
                <div style="text-align: center; font-size: 7px; margin-top: 2px;">Cashier Signature</div>
            </div>
            
            <div class="footer">
                <div><strong><?=$system_slogan;?></strong></div>
                <div style="margin-top: 3px;"><strong>THANK YOU!</strong></div>
                <div style="font-size: 6px; margin-top: 2px; color: #666;">This is a computer generated receipt</div>
            </div>
            
            <?php
            // Watermark logic
            if ($is_multi_invoice) {
                echo '<div id="watermark">MULTI-INVOICE</div>';
            } else {
                if ($class_name == 'JHSS') {
                    if ($year != $running_year || $sem != $running_sem) {
                        echo '<div id="watermark">ARREARS</div>';
                    }
                } else {
                    if ($year != $running_year || $term != $running_term) {
                        echo '<div id="watermark">ARREARS</div>';
                    }
                }
            }
            ?>
        </div>
        
        <div class="print-buttons">
            <button onclick="window.print()" class="btn btn-print">Print Receipt</button>
            <button onclick="window.close()" class="btn btn-back">Close</button>
        </div>
        </div>
        
        <script>
            // Auto-print when page loads
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
            
            // Close window function
            function closeWindow() {
                window.close();
            }
        </script>
    </body>
</html>