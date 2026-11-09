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
    $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
    $inv_number_len = strlen($invoice_code_f);

    $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;
    $current_user     = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');


    //currency
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

?>
<div class="row">

    <div class="col-md-12" id="searched_table_holder" style="display: block">
        <?php echo form_open(site_url('admin/bulk_invoice_delete'), array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'checkboxes_form2', 'enctype' => 'multipart/form-data'));?>
        <table class="table table-bordered normal_table" id="stable">
                <thead>
                    <tr>
                        <td></td>
                        <th><div><?php echo get_phrase('invoice_#');?></div></th>
                        <th><div><?php echo get_phrase('student');?></div></th>
                        <th><div><?php echo get_phrase('class');?></div></th>
                        <th><div><?php echo get_phrase('year_|_term');?></div></th>
                        <th><div><?php echo get_phrase('total');?></div></th>
                        <th><div><?php echo get_phrase('paid');?></div></th>
                        <th><div><?php echo get_phrase('status');?></div></th>
                        <th><div><?php echo get_phrase('date_created');?></div></th>
                        <th><div><?php echo get_phrase('options');?></div></th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php 

              $query = $this
                ->db
                ->select('invoice_code')
                ->distinct()
               // ->where($array)
               // ->limit($limit)
                //->order_by('invoice_code', 'desc')
                ->get('invoice');
    
        if($query->num_rows() > 0) {
            $invoices = $query->result();
            foreach ($invoices as $row) {

                if($row->invoice_code[0] == 0) {
                        $in_code = '_'.$row->invoice_code;
                } else {
                    $in_code = $row->invoice_code;
                }

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

                //current_class id
                $class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term))->row()->class_id;
                $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

                //add section A or B if the class has more than one section
                $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                $sec_name = '';
                if($class_has_more_sections > 1) {
                    $sec_name = $section_name;
                }
                $student_class = $class_name.' '. $class_name_numeric.$sec_name;

                //invoice_id
                $invoice_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->invoice_id;
                

                if ($a_due == 0) {
                    $status = '<button class="btn btn-success btn-xs">'.get_phrase('paid').'</button>';
                    //$payment_text = 'View Receipts';
                }elseif ($a_due < 0) {
                    $status = '<button class="btn btn-warning btn-xs">'.get_phrase('over_paid').'</button>';
                    //$payment_text = 'View Receipts';
                } else {
                    $status = '<button class="btn btn-danger btn-xs">'.get_phrase('unpaid').'</button>';
                    
                    //$payment_text = 'Take Payment';
                }

                $payment_option = '<li><a href="#" onclick="invoice_pay_modal('.$in_code.')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a></li><li class="divider"></li><li><a href="#" onclick="view_receipts_modal('.$in_code.')" style="color: #d803f8;"><i class="entypo-eye"></i>&nbsp;View Receipts</a></li><li class="divider"></li>';

                $bulk_invoice_sel = '
                        <input type="checkbox" class="checkbox" onclick="boxChecked2()" name="invoices_sel[]" value="'.$row->invoice_code.'">
                        
                        ';
                    
                
                $options = '<div class="btn-group">'.get_action_button().'<ul class="dropdown-menu dropdown-default pull-right" role="menu">'.$payment_option.'<li><a href="#" onclick="invoice_view_modal(\''.$in_code.'\')" style="color: blue;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_invoice').'</a></li><li class="divider"></li><li><a href="#" onclick="invoice_edit_modal(\''.$in_code.'\')" style="color: green;"><i class="entypo-pencil"></i>&nbsp;'.get_phrase('edit').'</a></li><li class="divider"></li><li><a href="#" onclick="invoice_delete_confirm(\''.$in_code.'\')" style="color: red;"><i class="entypo-trash"></i>&nbsp;'.get_phrase('delete').'</a></li></ul></div>';

                echo '<tr>
                            <td>'.$bulk_invoice_sel.'</td>
                            <td>'.$row->invoice_code.'</td>
                            <td>'.$this->crud_model->get_type_name_by_id('student',$student_id).'</td>
                            <td>'.$student_class.'</td>
                            <td>'.$year.'|'.$term.'</td>
                            <td>'.numfmt_format_currency($fmt, $total_amount, $currency).'</td>
                            <td>'.numfmt_format_currency($fmt, $amount_paid, $currency).'</td>
                            <td>'.$status.'</td>
                            <td>'.date('d M, Y', $creation_timestamp).'</td>
                            <td>'.$options.'</td>
                          </tr>

                ';
            }
        }
                    ?>
                </tbody>
                <tfoot id="tfooter2" style="display: none">
                    <tr>
                        <td colspan="10" align="center">
                           <input type="submit" name="submit_delete" id="submit_delete2" class="btn btn-danger" value="Delete Selected Invoices">
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div> <!--Searched Table Ends -->

</div>


<script type="text/javascript">
    $(document).ready(function() {
         $.fn.dataTable.ext.errMode = 'throw';
        $('#stable').dataTable(
            {
            'serverside': true
             }
            );

        boxChecked2();
    });


    function boxChecked2() {
        let checkboxes = $('#checkboxes_form2 td input[type="checkbox"]');
        let count_checked_buttons = checkboxes.filter(':checked').length;

        if(count_checked_buttons < 1) {
            $('#tfooter2').css('display', 'none');
        } else {
            $('#tfooter2').removeAttr('style');
        }
    }

    $('#checkboxes_form2').submit(function(event) {
        //event.preventDefault();

         let admin_level = <?php echo $admin_level; ?>;

        r = confirm('Are You Sure You Want To Delete The Selected Invoices? This Action Is Irreversible!!!');    
           
            if(r == 0) {
                event.preventDefault();
                return false;
            } else {
                if(admin_level == 1) {
                    return true;
                } else {
                    event.preventDefault();
                    confirm_modal('<?php echo site_url('admin/bulk_invoice_delete');?>', 'modal_invoice_warning');
                    return false;
                }
            }
        /**
        r = confirm('Are You Sure You Want To Delete The Selected Invoices? This Action Is Irreversible!!!');
        if(r == 0) {
            event.preventDefault();
            return false;
        } else {
            return true;
        } **/
    }); 

    function load_table() {
        $.ajax({
            url: '<?php echo site_url('admin/invoice_student_search/'); ?>',
            success: function(response) {
                
            }
        });
    }

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
        let admin_level = <?php echo $admin_level; ?>;

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        }

        if(admin_level == 1) {
            confirm_modal('<?php echo site_url('admin/invoice/delete/');?>' + invoice_code, 'modal_invoice_delete');
        } else {
           confirm_modal('<?php echo site_url('admin/invoice/delete/');?>' + invoice_code, 'modal_delete_warning');
            return false;
        }
    }


    
</script>

