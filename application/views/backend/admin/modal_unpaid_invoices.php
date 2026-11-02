<!--<div class="row">
    <div class="col-md-6"></div>
    <div class="col-md-6">
        <form class="form-horizontal">
            <div class="form-group">
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="class_filter" placeholder="Filter By Class" name="amount" value="" required/>
                </div>
                <div class="col-sm-4">
                    <select class="form-control select2" name="term_filer" id="term_filer" onchange="return get_invoices_by_term(this.value)">
                        <option value="">Filter By Term</option>
                        <?php
                            for($i = 1; $i <= 3; $i++) {
                                ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php
                            }
                        ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <input type="submit" class="btn btn-primary" value="Filter" />
                </div>
            </div>
        </form>
    </div>
</div>-->
<?php 

    //currency
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    // Get students with unpaid invoices - Query directly from invoice table where mute = '0'
    $query = "
        SELECT DISTINCT student_id
        FROM invoice
        WHERE due > 0
            AND mute = '0'
            AND can_delete != 'trash'
    ";
    $unpaid_invoices_students = $this->db->query($query)->result();

 
    $invoice_code_f = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
    $inv_number_len = strlen($invoice_code_f);
    
    // Calculate grand totals
    $grand_total_billed = 0;
    $grand_total_paid = 0;
    $grand_total_due = 0;
?>

<!-- Summary Card -->
<div class="row" style="margin-bottom: 15px;">
    <div class="col-md-12">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div style="text-align: center; padding: 15px;">
                        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Total Students Owing</div>
                        <div style="font-size: 42px; font-weight: bold;" id="total_students_count">-</div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div style="text-align: center; padding: 15px; border-left: 2px solid rgba(255,255,255,0.3);">
                        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Total Outstanding</div>
                        <div style="font-size: 42px; font-weight: bold;">
                            <sup style="font-size: 20px; opacity: 0.8;"><?php echo $currency; ?></sup>
                            <span id="total_due_display">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered" id="invoices">
            <thead>
                <tr>
                    <th><div><?php echo get_phrase('student');?></div></th>
                    <th><div><?php echo get_phrase('invoice#');?></div></th>
                    <th style="text-align: right;"><div><?php echo get_phrase('total');?></div></th>
                    <th style="text-align: right;"><div><?php echo get_phrase('paid');?></div></th>
                    <th style="text-align: right;"><div><?php echo get_phrase('amount_due');?></div></th>
                    <th><div>Term</div></th>
                    <th><div><?php echo get_phrase('year');?></div></th>
                    <th><div><?php echo get_phrase('options');?></div></th>
                    
                </tr>   
            </thead>
            <tbody>
                <?php 
                $student_count = 0;
                $invoice_count = 0;
                
                if (!empty($unpaid_invoices_students)) {
                    foreach($unpaid_invoices_students as $row):

                        $studentDataRow = $this->db->get_where('student', array('student_id' => $row->student_id));
                        if (!$studentDataRow || !$studentDataRow->num_rows()) {
                            continue; // Skip if student not found
                        }
                        $studentName = $studentDataRow->row()->name; 
                        
                        $enrollRow = $this->db->get_where('enroll', ['student_id' => $row->student_id, 'class_id IS NOT NULL'])->last_row();
                        if (!$enrollRow) {
                            continue; // Skip if no enrollment found
                        }

                        // Get unpaid invoices for this student - Query directly from invoice table
                        $invoice_query = "
                            SELECT DISTINCT invoice_code
                            FROM invoice
                            WHERE due > 0
                                AND student_id = ?
                                AND mute = '0'
                                AND can_delete != 'trash'
                        ";
                        $unpaid_invoices = $this->db->query($invoice_query, [$row->student_id])->result();

                        // Skip this student if they have no unpaid invoices (e.g., they were muted after invoice creation)
                        if (empty($unpaid_invoices)) {
                            continue;
                        }
                        
                        $student_count++;

                        foreach($unpaid_invoices as $inv):
                            $invoice_count++;

                            if($inv->invoice_code[0] == 0) {
                                $in_code = '_'.$inv->invoice_code;
                            } else {
                                $in_code = $inv->invoice_code;
                            }

                            //amount due - MUST filter by mute = '0' and due > 0
                            $this->db->select_sum('due');
                            $this->db->where('due >', 0);
                            $this->db->where('mute', '0');
                            $this->db->where('can_delete !=', 'trash');
                            $a_due = $this->db->get_where('invoice', array('student_id' => $row->student_id, 'invoice_code' => $inv->invoice_code))->row()->due;
                            $a_due = floatval($a_due);

                            //total amount - MUST filter by mute = '0'
                            $this->db->select_sum('amount');
                            $this->db->where('mute', '0');
                            $this->db->where('can_delete !=', 'trash');
                            $total_amount = $this->db->get_where('invoice', array('student_id' => $row->student_id, 'invoice_code' => $inv->invoice_code))->row()->amount;
                            $total_amount = floatval($total_amount);

                            //total amount paid - MUST filter by mute = '0'
                            $this->db->select_sum('amount_paid');
                            $this->db->where('mute', '0');
                            $this->db->where('can_delete !=', 'trash');
                            $amount_paid = $this->db->get_where('invoice', array('student_id' => $row->student_id, 'invoice_code' => $inv->invoice_code))->row()->amount_paid;
                            $amount_paid = floatval($amount_paid);
                            
                            // Accumulate totals
                            $grand_total_billed += $total_amount;
                            $grand_total_paid += $amount_paid;
                            $grand_total_due += $a_due;

                            //Get invoice row to extract class_id for this specific invoice
                            $inv_row = $this->db->get_where('invoice', array('student_id' => $row->student_id, 'invoice_code' => $inv->invoice_code))->row();
                            $creation_timestamp = $inv_row->creation_timestamp;
                            
                            // Get the class from the invoice's class_id (the class when invoice was created)
                            $invoice_class_id = $inv_row->class_id;
                            $invoiceClassName = getFullClassName($invoice_class_id);
                            
                            // Display student name with the class from the invoice
                            $studentInfo = $studentName . ' - ' . $invoiceClassName;


                            //invoice_id
                            $invoice_id = $inv_row->invoice_id;

                            //year
                            $year = $inv_row->year;

                            //term
                            $term = $inv_row->term;
                            $sem = $inv_row->sem;

                            if($term == '') {
                                $ts = 'Sem: '.$sem;
                            } else if($sem == '') {
                                $ts = 'Term: '.$term;
                            } 


                            if ($a_due == 0) {
                                $status = '<button class="btn btn-success btn-xs">Paid</button>';
                                $payment_option = '';
                            }elseif ($a_due < 0) {
                                $status = '<button class="btn btn-warning btn-xs">Over Paid</button>';
                                $payment_option = '';
                            } else {
                                $status = '<button class="btn btn-danger btn-xs">Unpaid</button>';
                                $payment_option = '<li><a href="#" onclick="invoice_pay_modal('.$row->student_id.')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a></li><li class="divider"></li>';
                            }
                                
                            
                            $options = '<div class="btn-group">'.get_action_button().'<ul class="dropdown-menu dropdown-default pull-right" role="menu">'.$payment_option.'<li><a href="#" onclick="invoice_view_modal(\''.$in_code.'\')" style="color: blue;"><i class="entypo-credit-card"></i>&nbsp;View_invoice</a></li><li class="divider"></li><li><a href="#" onclick="invoice_edit_modal('.$in_code.')" style="color: green;"><i class="entypo-pencil"></i>&nbsp;Edit</a></li>/*<li class="divider"></li><li><a href="#" onclick="invoice_delete_confirm('.$in_code.')" style="color: red;"><i class="entypo-trash"></i>&nbsp;Delete</a></li>*/</ul></div>';
                ?>
                        <tr>
                            <td><?php echo $studentInfo; ?></td>
                            <td><?php echo $inv->invoice_code; ?></td>
                            <td style="text-align: right;"><?php echo number_format($total_amount, 2); ?></td>
                            <td style="text-align: right;"><?php echo number_format($amount_paid, 2); ?></td>
                            <td style="font-weight: bold; text-align: right"><?php echo number_format($a_due, 2); ?></td>
                            <td><?php echo $ts; ?></td>
                            <td><?php echo $year; ?></td>
                            <td><?php echo $options; ?></td>
                        </tr>
                <?php 
                        endforeach;
                    endforeach;
                    ?>
                    <!-- Grand Total Row: 8 cells for 8 columns -->
                    <tr style="background-color: #f0f0f0; font-weight: bold; border-top: 3px solid #667eea;">
                        <td style="text-align: right; padding: 12px;"><strong>GRAND TOTAL:</strong></td>
                        <td></td>
                        <td style="text-align: right; font-size: 14px;"><?php echo number_format($grand_total_billed, 2); ?></td>
                        <td style="text-align: right; font-size: 14px;"><?php echo number_format($grand_total_paid, 2); ?></td>
                        <td style="text-align: right; font-size: 16px; color: #d9534f;"><?php echo number_format($grand_total_due, 2); ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php
                } else {
                ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">
                            <i class="entypo-info"></i> No unpaid invoices found for active students.
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">

    jQuery(document).ready(function($) {
        // Update summary card with totals (only students and outstanding)
        $('#total_students_count').text('<?php echo $student_count; ?>');
        $('#total_due_display').text('<?php echo number_format($grand_total_due, 2); ?>');
        
        // Initialize DataTable with custom settings to handle grouped rows
        $('#invoices').DataTable({
            bFilter: true,
            bPaginate: true,
            pageLength: 25,
            ordering: false, // Disable sorting to preserve student grouping
            dom: 'Blfrtip',
            footerCallback: function (row, data, start, end, display) {
                // This ensures the total row is always visible
            },
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'btn btn-success',
                    title: 'Unpaid Invoices Report',
                    messageTop: 'Total Outstanding: <?php echo $currency; ?> <?php echo number_format($grand_total_due, 2); ?>',
                    exportOptions: {
                        columns: ':not(:last-child)', // Exclude the Options column
                        format: {
                            body: function (data, row, column, node) {
                                // Strip HTML tags for export
                                return $('<div>').html(data).text();
                            }
                        }
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'btn btn-danger',
                    title: 'Unpaid Invoices Report',
                    messageTop: 'Total Outstanding: <?php echo $currency; ?> <?php echo number_format($grand_total_due, 2); ?>',
                    exportOptions: {
                        columns: ':not(:last-child)', // Exclude the Options column
                        format: {
                            body: function (data, row, column, node) {
                                return $('<div>').html(data).text();
                            }
                        }
                    },
                    customize: function(doc) {
                        // Add footer with totals (7 columns after excluding Options)
                        doc.content[1].table.body.push([
                            {text: 'GRAND TOTAL', colSpan: 2, bold: true, alignment: 'right'},
                            {},
                            {text: '<?php echo number_format($grand_total_billed, 2); ?>', bold: true, alignment: 'right'},
                            {text: '<?php echo number_format($grand_total_paid, 2); ?>', bold: true, alignment: 'right'},
                            {text: '<?php echo number_format($grand_total_due, 2); ?>', bold: true, alignment: 'right', color: 'red'},
                            '', // Term column
                            ''  // Year column
                        ]);
                    }
                },
                {
                    extend: 'print',
                    text: 'PRINT',
                    title: 'Unpaid Invoices Report',
                    messageTop: '<h3 style="text-align:center; color: #667eea;">Total Outstanding: <?php echo $currency; ?> <?php echo number_format($grand_total_due, 2); ?></h3>',
                    exportOptions: {
                        columns: ':not(:last-child)', // Exclude the Options column
                        format: {
                            body: function (data, row, column, node) {
                                return $('<div>').html(data).text();
                            }
                        }
                    }
                },
                'colvis',
            ],
            // Custom function to handle row rendering
            createdRow: function(row, data, dataIndex) {
                // Check if this is the grand total row
                if ($(row).find('td:first').text().includes('GRAND TOTAL')) {
                    $(row).addClass('grand-total-row');
                }
            }
        });
    });

    function invoice_pay_modal(invoice_code) {

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        }
        showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + invoice_code, 'take_payment');
    }

    function view_receipts_modal(invoice_code) {

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        }
        showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/');?>' + invoice_code, 'take_payment');
    }

    function invoice_view_modal(invoice_code) {

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        }

        $.ajax({
            url: '<?php echo site_url('admin/get_invoice_term/'); ?>'+ invoice_code,
            success: function(response) {
              showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/');?>' + invoice_code + '/' + response);   
            }
        });
        
    }

    function invoice_edit_modal(invoice_code) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_edit_invoice/');?>' + invoice_code);
    }

    function invoice_delete_confirm(invoice_code) {
        confirm_modal('<?php echo site_url('admin/invoice/delete/');?>' + invoice_code);
    }
</script>

