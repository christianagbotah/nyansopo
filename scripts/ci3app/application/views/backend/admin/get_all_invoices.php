 <?php           
    //currency
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    ?>
<?php echo form_open(site_url('admin/bulk_invoice_delete/all_invoices'), array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'checkboxes_form',  'enctype' => 'multipart/form-data'));?>
    <table class="table table-bordered normal_table" id="tinvoices" style="width:100%">
        <thead>
            <tr>
                <td><input type="checkbox" class="checkbox form-control" onclick="boxCheckedAll()" name="all_invoices_sel[]"></td>
                <th><div><?php echo get_phrase('invoice_#');?></div></th>
                <th><div><?php echo get_phrase('student');?></div></th>
                <th><div><?php echo get_phrase('class');?></div></th>
                <th><div><?php echo get_phrase('term');?></div></th>
                <th><div><?php echo get_phrase('total');?></div></th>
                <th><div><?php echo get_phrase('paid');?></div></th>
                <th><div><?php echo get_phrase('status');?></div></th>
                <th><div><?php echo get_phrase('date_created');?></div></th>
                <th><div><?php echo get_phrase('options');?></div></th>
                
            </tr>
        </thead>
        <tbody>
    <?php

if(count($page_data) > 0):
    foreach ($page_data as $row) {

                    if($row->invoice_code[0] == 0) {
                            $in_code = '_'.$row->invoice_code;
                    } else {
                        $in_code = $row->invoice_code;
                    }

                    $in_code = trim($in_code);

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
                    $invoice_year = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->year;
                    //year and term
                    $invoice_term = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->term;

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
                        $payment_text = 'View Receipts';
                    } else if($amount_paid == 0) {
                        $status = '<span class="btn btn-danger btn-xs">'.get_phrase('unpaid').'</span>';
                        
                        //$payment_text = 'Take Payment';
                    }

                    $payment_option = '<li><a href="#" onclick="invoice_pay_modal('.$student_id.')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a></li><li class="divider"></li><li><a href="#" onclick="view_receipts_modal('.$student_id.')" style="color: #d803f8;"><i class="entypo-eye"></i>&nbsp;View Receipts</a></li><li class="divider"></li>';
                    
                    $bulk_invoice_sel = '
                            <input type="checkbox" class="checkbox" onclick="boxChecked()" name="invoices_sel[]" value="'.$row->invoice_code.'">
                            
                            ';
                        
                    
                    $options = '<div class="btn-group">'.get_action_button().'<ul class="dropdown-menu dropdown-default pull-right" role="menu">'.$payment_option.'<li><a href="#" onclick="invoice_view_modal(\''.$in_code.'\')" style="color: blue;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_invoice').'</a></li><li class="divider"></li>

                                       <li><a href="#" onclick="invoice_delete_confirm(\''.$in_code.'\')" style="color: red;"><i class="entypo-trash"></i>&nbsp;'.get_phrase('delete').'</a></li></ul></div>';
                    $nchecked      = $bulk_invoice_sel;
                    $ninvoice_code = $row->invoice_code;
                    $nstudent = $this->crud_model->get_type_name_by_id('student',$student_id);
                    $nclass     = $student_class;
                    $nterm =  '<strong>TERM-'.$invoice_term.'</strong>';
                    $ntotal = '<strong>'.numfmt_format_currency($fmt, $total_amount, $currency).'</strong>';
                    $npaid  = '<strong>'.numfmt_format_currency($fmt, $amount_paid, $currency).'</strong>';
                    $nstatus = $status;
                    $ndate = date('d M, Y', $creation_timestamp);
                    $noptions = $options;
                    //$ninvoice_id = $row->invoice_id;

                    //insert the table data here
                    ?>
                    <tr>
                        <td><?= $nchecked; ?></td>
                        <td><?= $ninvoice_code; ?></td>
                        <td><?= $nstudent; ?></td>
                        <td><?= $nclass; ?></td>
                        <td><?= $nterm; ?></td>
                        <td><?= $ntotal; ?></td>
                        <td><?= $npaid; ?></td>
                        <td><?= $nstatus; ?></td>
                        <td><?= $ndate; ?></td>
                        <td><?= $noptions; ?></td>
                    </tr>
                    <?php
                    
                }
        else:
            echo '<tr><td align="center" colspan="10">No Record Found!</td></tr>';
        endif;

                        ?>
        </tbody>
            <tfoot id="tfooter" style="display: none">
                <tr>
                    <td colspan="10" align="center">
                       <input type="button" name="submit_delete" id="submit_delete" class="btn btn-danger" value="Delete Selected Invoices">
                    </td>
                </tr>
            </tfoot>
        </table>
</form>


<script type="text/javascript">
    $(document).ready(function($) {

            $.fn.dataTable.ext.errMode = 'throw';
        $('#tinvoices').DataTable({
            //"processing": true,
                //"serverSide": true,
            "columnDefs": [
            {
                "targets": [0],
                "orderable": false
            }]
        });
    });

    $('#submit_delete').click(function(event) {

        //Scroll to the top
          $('html, body').animate({
              scrollTop: ($('#top').offset().top )
          }, 1000);


         $('#modal_invoice_delete').modal('show', {backdrop: 'static'});

            $('#delete_invoice_link').click(function(event) {
                /* Act on the event */
                showCustomConfirm('Please Are You Really Sure You Want To Do This?', function() {
                    // On Yes
                    $('.close').click();

                    showAjaxModal_alert('DELETING INVOICE(S). PLEASE WAIT...', 'Loading');
                    //if(admin_level == 1) {

                            $('#checkboxes_form').submit();
                        return true;

                        /*} else {
                            confirm_modal('<?php echo site_url('admin/bulk_invoice_delete'); ?>', 'modal_invoice_warning');
                            return false;
                        }*/
                });
                // On No - nothing happens (modal closes)
            });
    });

    function invoice_delete_confirm(invoice_code) {


         confirm_modal('<?php echo site_url('admin/invoice/delete2/'); ?>' + invoice_code, 'modal_invoice_delete');
      
    }
</script>