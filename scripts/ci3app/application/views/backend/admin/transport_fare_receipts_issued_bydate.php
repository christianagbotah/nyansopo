<hr>
<div class="row">
    <center><h4>YOU SELECTED TRANSPORT FARE FOR <?= strtoupper(date('l M d, Y', strtotime($param2))); ?></h4></center>
</div>
<hr>


<div class="row">
    <div class="col-md-12" style="">
        <div class="col-md-6">
            <div class="col-md-12">
                <div class="row">
                <div class="col-md-12">          
                    <div class="tile-stats tile-green" style="overflow: visible; min-height: 180px;">
                        <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
                        <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'Amount'; ?></sub> <div class="num" data-start="0"
                                data-postfix="" data-duration="500" data-delay="0" id="receipt_date_rec"></div>

                        <sub style="font-weight: bold; color: #ffffff; font-size: 15px; margin-top: -69px;" class="pull-right"><?php echo 'Quantity'; ?> <label class="label label-danger" id="receipt_date_rec_qty"></label></sub>
                        
                        <h3><?php echo get_phrase('total_receipts_for_transport_fare');?></h3>
                        <p id="receipt_date_rec_date"></p>

                        <div class="col-md-4 pull-right" style="">
                            <?php echo form_open(site_url('admin/update_class/search'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'class_update_form')); ?>
                            <label class="form-control-labels" style="color: #fff;">Filter By Class</label>
                            <select name="class_id" class="form-control select2" onchange="class_update()" id="receipt_class_selection">
                            <option value=""><?php echo get_phrase('click_here');?></option>
                            <?php
                                $classes = $this->db->get('class')->result_array();
                                foreach($classes as $row):

                                    //add section A or B if the class has more than one section
                                    $class_name         =   $this->db->get_where('class' , array('class_id' => $row['class_id']))->row()->name;
                                    $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $row['class_id']))->row()->name_numeric;
                                    $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                    if($class_name == 'CRECHE') {
                                        $creche_name = ucwords(strtolower($class_name)).$sec_name;
                                    } else {
                                        $creche_name = ucwords(strtolower($class_name)). ' '. $class_name_numeric.$sec_name;
                                    }
                                                        
                            ?>
                                            
                            <option value="<?php echo $row['class_id'];?>"
                                ><?php echo $creche_name; ?></option>
                                            
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
                                data-postfix="" data-duration="500" data-delay="0" id="receipt_date_rec2"></div>

                        <sub style="font-weight: bold; color: #ffffff; font-size: 15px; margin-top: -69px;" class="pull-right"><?php echo 'Quantity'; ?> <label class="label label-danger" id="receipt_date_rec_qty2"></label></sub>
                        
                        <h3><?php echo get_phrase('total_receipts_for_transport_fare');?></h3>
                        <p id="receipt_date_rec_date2"></p>

                        <div class="col-md-8 pull-right" style="">
                            <?php echo form_open(site_url('admin/update_issuer'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'issuer_update_form')); ?>
                            <label class="form-control-labels" style="color: #fff;">Filter By Issuer</label>
                            <select name="class_id" class="form-control select2" onchange="issuer_update()" id="receipt_issuer_selection">
                            <option value=""><?php echo get_phrase('click_here');?></option>
                            <?php

                                //selecting unique issuers' ids from the payment table
                                $this->db->select('issuer_id');
                                $this->db->distinct();
                                $this->db->where('can_delete !=', 'trash');
                                $issuers_ids = $this->db->get_where('payment' , array('day_timestamp'=> strtotime($param2), 'invoice_id' => NULL, 'title' => 'Transport Fare'))->result_array();
                                $issuers_ids_array = array();
                                $issuers_ids_array_teachers = array();

                                foreach($issuers_ids as $id) {
                                    if(substr($id['issuer_id'], 0, 1) == 't') { //for teachers
                                        array_push($issuers_ids_array_teachers, substr($id['issuer_id'], 1));
                                    } else { //for admins
                                        array_push($issuers_ids_array, $id['issuer_id']);
                                    }   
                                }

                                if(count($issuers_ids_array_teachers) > 0) {
                                    //teachers
                                    $this->db->where_in('teacher_id', $issuers_ids_array_teachers);
                                    $issuers_names_row_teachers = $this->db->get('teacher');
                                    $issuers_names_teachers = $issuers_names_row_teachers->result_array();

                                    if($issuers_names_row_teachers->num_rows() > 0) {
                                        foreach($issuers_names_teachers as $rowt):

                                            ?>
                                            <option value="<?php echo 't'.$rowt['teacher_id'];?>"
                                    ><?php echo $rowt['name']; ?><small>(Teacher)</small></option>
                                            <?php
                                        endforeach;

                                    }
                                }

                                if(count($issuers_ids_array) > 0) {
                                    //admins
                                    $this->db->where_in('admin_id', $issuers_ids_array);
                                    $issuers_names_row = $this->db->get('admin');
                                    $issuers_names = $issuers_names_row->result_array();

                                    if($issuers_names_row->num_rows() > 0) {
                                    foreach($issuers_names as $row):

                                    //get the issuer's designation
                                    $account_type = $this->db->get_where('admin', array('admin_id' => $row['admin_id']))->row()->level;

                                    if($account_type == 1) {
                                        $account_type = 'Super Admin';
                                    } else if($account_type == 2) {
                                        $account_type = 'Admin';
                                    } else if($account_type == 3) {
                                        $account_type = 'Accountant';
                                    }                  
                                    ?>
                                                    
                                    <option value="<?php echo $row['admin_id'];?>"
                                        ><?php echo $row['name']; ?><small>(<?= $account_type; ?>)</small></option>
                                                    
                                    <?php endforeach;
                                        }
                                }
                                

                                ?>
                                
                        </select>
                        </form>
                        </div>
                    </div>
                    
                </div>
            </div>
            </div>
            
        </div>
    </div>



    <hr>
    <div class="col-md-12" id="table_modal_holder"></div>
</div>


<script type="text/javascript">

    $(function($) {
        //$('.datatable').DataTable();
        
        //load_data();
        get_transport_fare_receipts_by_date();

    });

    //update invoices onload
    function get_transport_fare_receipts_by_date() {
        let selected_date = $('#date_sel').val();

        $.ajax({
            url: '<?php echo site_url('admin/get_transport_fare_receipt_bydate/search/'); ?>' + selected_date,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#receipt_date_rec').text(response[0].total_amount);
                $('#receipt_date_rec_date').text('Received On: ' + response[0].date_chosen);
                $('#receipt_date_rec_qty').text(response[0].total_number);

                $('#receipt_date_rec2').text(response[0].total_amount);
                $('#receipt_date_rec_date2').text('Received On: ' + response[0].date_chosen);
                $('#receipt_date_rec_qty2').text(response[0].total_number);
            }
        });
    }

    //updating receipts when issuer is selected
    function issuer_update() {
        let issuer_id = $('#receipt_issuer_selection').val();
        let selected_date = $('#date_sel').val();

        $.ajax({
            url: '<?php echo site_url('admin/transport_fare_issuer_update/'); ?>' + selected_date + '/' + issuer_id,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#receipt_date_rec2').text(response[0].total_amount);
                $('#receipt_date_rec_qty2').text(response[0].total_number);
            }
        });
    }

    //updating receipts when class is selected
    function class_update() {
        let class_id = $('#receipt_class_selection').val();
        let selected_date = $('#date_sel').val();

        $.ajax({
            url: '<?php echo site_url('admin/transport_fare_class_update/'); ?>' + selected_date + '/' + class_id,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#receipt_date_rec').text(response[0].total_amount);
                $('#receipt_date_rec_qty').text(response[0].total_number);
            }
        });
    }

    function load_data() {
        let selected_date = '<?php echo $param2; ?>';
        $.ajax({
            url: '<?php echo site_url('admin/get_transport_fare_receipt_bydate_modal/') ?>' + selected_date,
            beforeSend: function() {
                $('#table_modal_holder').html('<center><h2 style="color: red;">Loading... Please Wait.</h2></center>');
            },
            success: function(response) {
               $('#table_modal_holder').html('<table class="table table-responsive table-bordered datatable" id="receipts_tab"><thead><tr><th><div><?php echo get_phrase('iD_no');?></div></th><th><div><?php echo get_phrase('photo');?></div></th><th><div><?php echo get_phrase('name');?></div></th><th><div><?php echo get_phrase('class');?></div></th><th><div><?php echo get_phrase('receipt#');?></div></th><th class="span3" style="text-align: right"><div><?php echo get_phrase('amount');?></div></th><th><div><?php echo get_phrase('date');?></div></th><th><div><?php echo 'Year|Term';?></div></th><th><div><?php echo get_phrase('issuer');?></div></th><th><div><?php echo 'Action';?></div></th></tr></thead><tbody>' + response + '</tbody></table>');

                $('#receipts_tab').DataTable();
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
