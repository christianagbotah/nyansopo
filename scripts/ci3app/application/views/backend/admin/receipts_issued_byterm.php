<hr>
<div class="row">
    <center><h4>YOU SELECTED YEAR <?= explode('-', $param2)[1]. ' | TERM '. $param3; ?></h4></center>
</div>
<hr />


<div class="row">
    <div class="col-md-12" style="">
        <div class="col-md-6">
            <div class="col-md-12">
                <div class="row">
                <div class="col-md-12">          
                    <div class="tile-stats tile-green" style="overflow: visible; min-height: 180px;">
                        <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
                        <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                                data-postfix="" data-duration="500" data-delay="0" id="receipt_term_rec"></div>

                        <sub style="font-weight: bold; color: #ffffff; font-size: 15px; margin-top: -69px;" class="pull-right"><?php echo 'Quantity'; ?> <label class="label label-danger" id="receipt_term_rec_qty"></label></sub>
                        
                        <h3><?php echo get_phrase('total_receipts_for_issued_invoices');?></h3>
                        <p id="receipt_term_rec_date"></p>

                        <div class="col-md-4 pull-right" style="">
                            <?php echo form_open(site_url('admin/update_class/search'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'class_update_form')); ?>
                            <label class="form-control-labels" style="color: #fff;">Filter By Class</label>
                            <select name="class_id" class="form-control select2" onchange="class_update_term()" id="receipt_class_selection_term2">
                            <option value=""><?php echo get_phrase('click_here');?></option>
                            <?php
                                $this->db->select('c.class_id, c.name, c.name_numeric, s.name as section_name');
                                $this->db->from('class c');
                                $this->db->join('section s', 'c.class_id = s.class_id', 'left');
                                $classes = $this->db->get()->result_array();
                                
                                $class_counts = array();
                                foreach($classes as $c) {
                                    $key = $c['name'].'_'.$c['name_numeric'];
                                    $class_counts[$key] = isset($class_counts[$key]) ? $class_counts[$key] + 1 : 1;
                                }
                                
                                foreach($classes as $row):
                                    $key = $row['name'].'_'.$row['name_numeric'];
                                    $sec_name = ($class_counts[$key] > 1) ? $row['section_name'] : '';
                                    
                                    if($row['name'] == 'CRECHE') {
                                        $creche_name = ucwords(strtolower($row['name'])).$sec_name;
                                    } else {
                                        $creche_name = ucwords(strtolower($row['name'])). ' '. $row['name_numeric'].$sec_name;
                                    }
                            ?>
                            <option value="<?php echo $row['class_id'];?>"><?php echo $creche_name; ?></option>
                            <?php endforeach;?>
                        </select>
                        </form>
            </div>
                    </div>
                    
                </div>
            </div>
            </div>
            
        </div>

        <div class="col-md-6">
            <div class="col-md-12">
                <div class="row">
                <div class="col-md-12">          
                    <div class="tile-stats tile-pink" style="overflow: visible; min-height: 180px;">
                        <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
                        <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                                data-postfix="" data-duration="500" data-delay="0" id="receipt_term_rec2"></div>

                        <sub style="font-weight: bold; color: #ffffff; font-size: 15px; margin-top: -69px;" class="pull-right"><?php echo 'Quantity'; ?> <label class="label label-danger" id="receipt_term_rec_qty2"></label></sub>
                        
                        <h3><?php echo get_phrase('total_receipts_for_issued_invoices');?></h3>
                        <p id="receipt_term_rec_date2"></p>

                        <div class="col-md-8 pull-right" style="">
                            <?php echo form_open(site_url('admin/update_issuer'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'issuer_update_form')); ?>
                            <label class="form-control-labels" style="color: #fff;">Filter By Issuer</label>
                            <select name="class_id" class="form-control select2" onchange="issuer_update_term()" id="receipt_issuer_selection2">
                            <option value=""><?php echo get_phrase('click_here');?></option>
                            <?php
                                $this->db->select('a.admin_id, a.name, a.level');
                                $this->db->from('payment p');
                                $this->db->join('admin a', 'p.issuer_id = a.admin_id');
                                $this->db->where('p.year', $param2);
                                $this->db->where('p.term', $param3);
                                $this->db->where('p.invoice_id IS NOT NULL');
                                $this->db->where('p.can_delete !=', 'trash');
                                $this->db->group_by('a.admin_id');
                                $issuers_names = $this->db->get()->result_array();

                                foreach($issuers_names as $row):
                                    $account_types = array(1 => 'Super Admin', 2 => 'Admin', 3 => 'Accountant');
                                    $account_type = isset($account_types[$row['level']]) ? $account_types[$row['level']] : 'User';
                            ?>
                            <option value="<?php echo $row['admin_id'];?>"><?php echo $row['name']; ?><small>(<?= $account_type; ?>)</small></option>
                            <?php endforeach; ?>
                        </select>
                        </form>
                        </div>
                    </div>
                    
                </div>
            </div>
            </div>
            
        </div>
    </div>

<hr />


<div class="row">
    <div class="col-md-12 mb-5" id="table_modal_holder">

                
    </div>
</div>


<script type="text/javascript">

    $(function($) {
        //$('.datatable').DataTable();
        

        load_data();
        get_receipts_by_term2();
    });

    //update invoices onload
    function get_receipts_by_term2() {
        let term = $('#term').val();
        let year = $('#year').val();
        //let sem = $('#sem').val();

        $.ajax({
            url: '<?php echo site_url('admin/get_receipt_byterm/search/'); ?>' + year + '/' + term,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#receipt_term_rec').text(response[0].total_amount);
                $('#receipt_term_rec_date').text('Received In: ' + response[0].duration);
                $('#receipt_term_rec_qty').text(response[0].total_number);

                $('#receipt_term_rec2').text(response[0].total_amount);
                $('#receipt_term_rec_date2').text('Received In: ' + response[0].duration);
                $('#receipt_term_rec_qty2').text(response[0].total_number);
            }
        });
    }


     //updating receipts when issuer is selected
    function issuer_update_term() {
        let issuer_id = $('#receipt_issuer_selection2').val();
        let term = $('#term').val();
        //let sem = $('#sem').val();
        let year = $('#year').val();

        $.ajax({
            url: '<?php echo site_url('admin/issuer_update_term/'); ?>' + issuer_id + '/' + year + '/' + term,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#receipt_term_rec2').text(response[0].total_amount);
                $('#receipt_term_rec_qty2').text(response[0].total_number);
                $('#receipt_term_rec_date2').text('Received In: ' + response[0].duration);
            }
        });
    }

    //updating receipts when class is selected
    function class_update_term() {
        let class_id = $('#receipt_class_selection_term2').val();
        let term = $('#term').val();
        //let sem = $('#sem').val();
        let year = $('#year').val();

        $.ajax({
            url: '<?php echo site_url('admin/class_update_term/'); ?>' + class_id + '/' + year + '/' + term,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#receipt_term_rec').text(response[0].total_amount);
                $('#receipt_term_rec_qty').text(response[0].total_number);
                $('#receipt_term_rec_date').text('Received In: ' + response[0].duration);
            }
        });
    }


    function load_data() {
        let year = '<?php echo $param2; ?>';
        let term = '<?php echo $param3; ?>';
        //let sem = '<?php echo $param4; ?>';
        
        $.ajax({
            url: '<?php echo site_url('admin/get_receipt_byterm_modal/') ?>' + year + '/' + term,
            beforeSend: function() {
                $('#table_modal_holder').html('<center><h2 style="color: red;">Loading... Please Wait.</h2></center>');
            },
            success: function(response) {
               $('#table_modal_holder').html('<table class="table table-responsive table-bordered datatable" id="receipts_tab2"><thead><tr><th><div><?php echo get_phrase('iD_no');?></div></th><th><div><?php echo get_phrase('photo');?></div></th><th><div><?php echo get_phrase('name');?></div></th><th><div><?php echo get_phrase('class');?></div></th><th><div><?php echo get_phrase('receipt#');?></div></th><th class="span3" style="text-align: right"><div><?php echo get_phrase('amount');?></div></th><th><div><?php echo get_phrase('date');?></div></th><th><div><?php echo 'Year|Term';?></div></th><th><div><?php echo get_phrase('issuer');?></div></th><th><div><?php echo 'Action';?></div></th></tr></thead><tbody>' + response + '</tbody></table>');

                $('#receipts_tab2').DataTable({
                    bFilter: false,
                    bPaginate: false,
                    dom: 'Blfrtip',
                    buttons: [
                        {
                            extend: 'excel',
                            text: 'Export to Excel',
                            className: 'btn btn-success',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },

                        'colvis',
                    ]
                });
            }
        })
        
    }

function check_sms_status() {
        var active_sms_service = '<?php echo $active_sms_service; ?>';
        if(active_sms_service == '' || active_sms_service == 'disabled' || active_sms_service == null) {
            alert('No active SMS service found. Please go to System Settings and activate SMS service and try again');
            toastr.error('No active SMS service found. Please go to System Settings and activate SMS service and try again');
            $('.pt_link').removeAttr('href');
            $('.pt_link').attr({href: '#'});
            return false;

        }
    }

</script>
