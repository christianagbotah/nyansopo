
<?php 
    $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
    $inv_number_len = strlen($invoice_code_f);


    $user_id = $this->session->userdata('login_user_id');
    $admin_level = $this->db->get_where('admin', array('admin_id' => $user_id))->row()->level;

    
    //currency
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        //$invoices = $this->ajaxload->all_invoices_page(); //Database query from the ajaxload model

    $class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'mute' => '0'))->last_row()->class_id;

    $class_name = $this->crud_model->get_class_name($class_id);

?>
<div class="row">
    <!-- Global Search Filter -->
    <div class="col-md-12 mb-4">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4 style="color: white; margin-bottom: 1rem;"><i class="fa fa-search"></i> <?php echo get_phrase('global_search'); ?></h4>
            <div class="row">
                <div class="col-md-5">
                    <input type="text" id="global_search_input" class="form-control" placeholder="<?php echo get_phrase('search_by_student_name_student_code_or_invoice_code'); ?>" style="padding: 0.75rem; border-radius: 8px; font-size: 1rem;">
                </div>
                <div class="col-md-2">
                    <button type="button" id="global_search_btn" class="btn btn-light btn-block" style="padding: 0.75rem; border-radius: 8px; font-weight: 600;">
                        <i class="fa fa-search"></i> <?php echo get_phrase('search'); ?>
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="clear_search_btn" class="btn btn-warning btn-block" style="padding: 0.75rem; border-radius: 8px; font-weight: 600;">
                        <i class="fa fa-times"></i> <?php echo get_phrase('clear'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12" id="table_holder">
        <?php echo form_open(site_url('admin/bulk_invoice_delete/invoices_show/true'), array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'checkboxes_form',  'enctype' => 'multipart/form-data'));?>
                    

                    <table class="w-full text-xl text-left text-gray-500 dark:text-gray-400 datatable normal_table" id="tinvoices">
                      <thead class="text-xl text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                          <tr>
                              <th scope="col" class="p-4">
                                  
                              </th>
                              <th scope="col" class="px-4 py-3 text-left"><?php echo get_phrase('invoice_#');?></th>
                              <th scope="col" class="px-4 py-3"><?php echo get_phrase('student');?></th>
                              <th scope="col" class="px-4 py-3"><?php echo get_phrase('class');?></th>
                              <th scope="col" class="px-4 py-3"><?php echo get_phrase('year_|_term');?></th>
                              <th scope="col" class="px-4 py-3" align="left"><?php echo get_phrase('total');?></th>
                              <th scope="col" class="px-4 py-3"><?php echo get_phrase('paid');?></th>
                              <th scope="col" class="px-4 py-3"><?php echo get_phrase('balance');?></th>
                              <th scope="col" class="px-4 py-3" align="right"><?php echo get_phrase('status');?></th>
                              <th scope="col" class="px-4 py-3"><?php echo get_phrase('date_created');?></th>
                              <th scope="col" class="px-4 py-3"><?php echo get_phrase('options');?></th>
                          </tr>
                      </thead>
                      <tbody>
                    <?php                    

                    foreach ($invoices as $row) {

                    //if($row->invoice_code[0] == 0) {
                            //$in_code = 'a'.$row->invoice_code;
                    //} else {
                        $in_code = $row->invoice_code;
                    //}

                    //amount due
                    $this->db->select_sum('due');
                    $a_due = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->due;

                    //total amount
                    $this->db->select_sum('amount');
                    $total_amount = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->amount;

                    //total amount paid
                    $this->db->select_sum('amount_paid');
                    $amount_paid = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->amount_paid;

                    //year and term
                    $year = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->year;
                    //year and term
                    $term = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->term;

                    //creation date
                    $creation_timestamp = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->creation_timestamp;

                    //student_id
                    $student_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->student_id;

                    //invoice_class_id
                    $invoice_class_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->class_id;

                    //current_class id
                    
                    $class_name = $this->db->get_where('class', array('class_id' => $invoice_class_id))->row()->name;
                    $class_name_numeric = $this->db->get_where('class', array('class_id' => $invoice_class_id))->row()->name_numeric;

                    //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('class_id' => $invoice_class_id))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = $section_name;
                    }
                    $student_class = $class_name.' '. $class_name_numeric.$sec_name;

                    //invoice_id
                    $invoice_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->invoice_id;
                    

                    if ($a_due == 0) {
                        $status = '<span class="btn btn-success btn-xs">'.get_phrase('paid').'</span>';
                        //$payment_text = 'View Receipts';
                    } elseif ($a_due < 0) {
                        $status = '<span class="btn btn-warning btn-xs">'.get_phrase('over_paid').'</span>';
                        //$payment_text = 'View Receipts';
                    } elseif ($a_due > 0 && $amount_paid > 0) {
                        $status = '<span class="btn btn-info btn-xs">'.get_phrase('Part Payment').'</span>';
                        //$payment_text = 'View Receipts';
                    } else if($amount_paid == 0) {
                        $status = '<span class="btn btn-danger btn-xs">'.get_phrase('unpaid').'</span>';
                        
                        //$payment_text = 'Take Payment';
                    }

                    $invoice_can_delete_status = $this->financial_report_model->getInvoiceCanDeleteStatus($row->invoice_code);

                    $payment_option = '<li><a href="#" onclick="invoice_pay_modal('.$student_id.')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;'.get_phrase('take_payment').'</a></li><li class="divider"></li><li><a href="#" onclick="view_receipts_modal('.$student_id.')" style="color: #0891b2;"><i class="entypo-eye"></i>&nbsp;'.get_phrase('view_receipts').'</a></li><li class="divider"></li>';
                    
                    

                    $approvedCounter = 0;

                    if($admin_level != 1):
                        if($invoice_can_delete_status == 'request') {

                            $bulk_invoice_sel = '<div class="flex flex-col gap-2 items-center">
                            <i class="fa-solid fa-spinner fa-pulse text-yellow-400 font-semibold"></i>
                            <div class="text-sm font-semibold text-yellow-600">Pending</div>
                            </div>';

                        } else if($invoice_can_delete_status == 'declined') {

                            $bulk_invoice_sel = '<div class="flex flex-col gap-2 items-center">
                            <i class="fa-solid fa-x text-red-600 text-lg font-semibold"></i>
                            <span class="text-red-600 text-sm">Declined</span>
                            </div>';

                        } else if($invoice_can_delete_status == 'approved') { //invoice approved
                            
                            $whoSentRequest = $row->delete_request_issuer_id; //is it the same person who sent the request that is viewing this page now?

                            if($current_user == $whoSentRequest): //yes
                                $approvedCounter++; //we now confirm it is a true approval for this user

                                $bulk_invoice_sel = '<div class="flex flex-col gap-2 items-center">
                                    <input type="checkbox" class="checkbox" onclick="return false;" name="invoices_sel[]" checked value="'.$row->invoice_code.'">
                                    <span class="text-green-600 font-semibold text-sm">Approved</span>
                                    </div>';

                            else: //else we don't allow the user to proceed with this approval
                                $bulk_invoice_sel = '<div class="flex flex-col gap-2 items-center">
                                    <i class="fa-solid text-green-400 fa-check-circle text-yellow-400 font-semibold"></i>
                                    <span class="text-green-600 font-semibold text-sm">Approved</span>
                                    </div>';
                            endif;

                        } else {

                            $bulk_invoice_sel = '
                            <input type="checkbox" class="checkbox" onclick="boxChecked('.$approvedCounter.')" name="invoices_sel[]" value="'.$row->invoice_code.'">
                            
                            ';
                        }

                    else:
                        //for the admin
                        $bulk_invoice_sel = '<div class="flex gap-2 items-center">
                                <input type="checkbox" class="checkbox" onclick="boxChecked('.$approvedCounter.')" name="invoices_sel[]" value="'.$row->invoice_code.'">
                                <span class="text-sm font-semibold">'.$invoice_can_delete_status.'</span>
                            </div>';
                    endif;
                    
                    $options = '<div class="btn-group">'.get_action_button().'<ul class="dropdown-menu dropdown-default pull-right" role="menu">'.$payment_option.'<li><a href="#" onclick="invoice_view_modal(\''.$in_code.'\')" style="color: blue;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_invoice').'</a></li><li class="divider"></li>

                                        <li><a href="#" onclick="bulk_invoice_view_modal('.$student_id.')" style="color: black;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_bulk_invoice').'</a></li><li class="divider"></li>

                                        <li><a href="#" onclick="loadModalContent(\'createModal\', \''.site_url('admin/invoice_modification_modal/').'\' + \''.$in_code.'\', \'<i class=\"fa fa-edit\"></i> Modify Invoice\')" style="color: green;"><i class="entypo-pencil"></i>&nbsp;' . get_phrase('modify_invoice') . '</a></li><li class="divider"></li>

                                        </ul></div>';
                    $nchecked      = $bulk_invoice_sel;
                    $ninvoice_code = $row->invoice_code;
                    $nstudent = $this->crud_model->get_type_name_by_id('student',$student_id);
                    $nclass     = $student_class;
                    $nterm =  '<strong>'.explode('-', $year)[1].'|'.$term.'</strong>';
                    $ntotal = '<strong>'.numfmt_format_currency($fmt, $total_amount, $currency).'</strong>';
                    $npaid  = '<strong>'.numfmt_format_currency($fmt, $amount_paid, $currency).'</strong>';
                    $nbalance  = '<strong>'.numfmt_format_currency($fmt, $total_amount - $amount_paid, $currency).'</strong>';
                    $nstatus = $status;
                    $ndate = date('d M, Y', $creation_timestamp);
                    $noptions = $options;
                    //$ninvoice_id = $row->invoice_id;

                    //insert the table data here
                    ?>
                    <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td align="center"><?= $nchecked; ?></td>
                        <td align="left"><?= $ninvoice_code; ?></td>
                        <td><?= $nstudent; ?></td>
                        <td><?= $nclass; ?></td>
                        <td><?= $nterm; ?></td>
                        <td><?= $ntotal; ?></td>
                        <td><?= $npaid; ?></td>
                        <td><?= $nbalance; ?></td>
                        <td><?= $nstatus; ?></td>
                        <td><?= $ndate; ?></td>
                        <td><?= $noptions; ?></td>
                    </tr>

                    <?php
                        }
                    ?>

                    </tbody>
                    <tfoot id="tfooter" style="display: none">
                        <tr>
                            <td colspan="10" align="center">
                              

                               <?php
                                    if($admin_level == 1) {?>

                                         <input type="submit" name="submit_delete" id="submit_delete" class="btn btn-danger rounded-lg font-bold uppercase" value="Delete Selected Invoices">
                                         <?php     
                                    } else {

                                        ?>

                                         <input type="submit" name="submit_delete_request" id="submit_delete_request" class="btn btn-danger rounded-lg font-bold uppercase" value="Request to delete selected invoices">
                                         <?php  
                                    }
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
        </form>
    </div>

</div>
<script type="text/javascript">
    $(document).ready(function($) {
        $('#tinvoices').DataTable();

        let approvedCounter = Number(<?=$approvedCounter;?>);
        if(approvedCounter > 0) {

            boxChecked(approvedCounter, true); //we only display the button if this other user is the one who made the request
        }
        
    });


    $('#checkboxes_form').submit(function(event) {
     event.preventDefault();

        let admin_level = <?php echo $admin_level; ?>;
        let approvedCounter = Number(<?=$approvedCounter;?>);

        if(admin_level == 1) {
                    
            confirm_modal('<?php echo site_url('admin/bulk_invoice_delete'); ?>', 'modal_invoice_delete', 'checkboxes_form', <?=$class_id;?>);

        } else {

            if(approvedCounter > 0) {
                //We have approval for some of the invoices here, so we can display the direct delete button now

                confirm_modal('<?php echo site_url('admin/bulk_invoice_delete'); ?>', 'modal_invoice_delete', 'checkboxes_form', <?=$class_id;?>);
                return false;

            } else {

                confirm_modal($(this).attr('action'), 'modal_delete_warning', 'checkboxes_form', <?=$class_id;?>);
                return false;
            }

            
            
        }
    });



    // Global Search Functionality
    $('#global_search_btn').click(function() {
        const searchTerm = $('#global_search_input').val().trim();
        if(searchTerm) {
            $('#tinvoices').DataTable().search(searchTerm).draw();
        }
    });

    $('#global_search_input').keypress(function(e) {
        if(e.which == 13) {
            $('#global_search_btn').click();
        }
    });

    $('#clear_search_btn').click(function() {
        $('#global_search_input').val('');
        $('#tinvoices').DataTable().search('').draw();
    });

</script>
<!--
<script type="text/javascript">
    $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'throw';
        $('#tinvoices').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                "url": "<?php //echo site_url('admin/get_invoices') ?>",
                "dataType": "json",
                "type": "POST",
            },
            "columns": [
                { "data": "checked" },
                { "data": "invoice_code" },
                { "data": "student" },
                { "data": "class" },
                { "data": "term" },
                //{ "data": "title" },
                { "data": "total" },
                { "data": "paid" },
                { "data": "status" },
                { "data": "date" },
                { "data": "options" },
                
            ],
            "columnDefs": [
                {
                    "targets": [3,4,5,6,7],
                    "orderable": false
                },
            ]
        });
    });
</script> -->