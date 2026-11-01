
<div class="container">                
<hr />
<div class="row" style="padding-top: 15px;">
    <a onClick="PrintElem('#print')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
           Print Debtors List
            <i class="glyphicon glyphicon-print"></i>
    </a>
</div>
<br>

<div class="row">
    <div class="col-md-12">
        <div id="print">
            <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>
            <style type="text/css">
                 #mark_print th, #grade_table th, #conducts th {
                    background-color: grey;
                    border: 2px solid #e2dddd;
                    padding: 5px;
                    color: white;
                    border-bottom: 2px solid red;
                 }

                td {
                    padding: 5px;
                    text-align: center;
                }

                .al {
                    text-align: left !important;
                }

                .ar {
                    text-align: right !important;
                    font-weight: bolder;
                }

                @media Print{
                #mark_print_length, #mark_print_filter, #mark_print_info, #mark_print_paginate {
                        display: none;
                    }
                }

            </style>

        <ul class="nav nav-tabs bordered">
            <li class="active">
                <a href="#home" data-toggle="tab">
                    <span class="visible-xs"><i class="glyphicon glyphicon-users"></i></span>
                    <span class="hidden-xs">Feeding Fee, Classes Fee & Transport Fare Debtors As At <?= date('l M d, Y', $timestamp);?></span>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="home">

                <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" id="mark_print">
                    <thead>
                        <tr>
                            <th colspan="9" style="text-align: center; font-weight: bold; font-size: 16px"><?php echo strtoupper('Feeding Fee, Classes Fee & Transport Fare Debtors As At '.date('l M d, Y', $timestamp));?></th>
                        </tr>
                        <tr>
                            <th width="80"><div><?php echo get_phrase('iD_no');?></div></th>
                            <th width="80" style="text-align: center"><div><?php echo get_phrase('photo');?></div></th>
                            <th><div><?php echo get_phrase('name');?></div></th>
                            <th class="span3"><div><?php echo get_phrase('address');?></div></th>
                            <th><div><?php echo get_phrase('guardian_contact');?></div></th>
                            <th><div><?php echo get_phrase('class');?></div></th>
                            <th><div><?php echo get_phrase('feeding_bal');?></div></th>
                            <th><div><?php echo get_phrase('classes_bal');?></div></th>
                            <th><div><?php echo get_phrase('transport_bal');?></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                                //feeding and classes fee
                                $this->db->select('student_id');
                                $this->db->distinct();
                                $this->db->from('feeding_fee');
                                $this->db->where('timestamp <=', $timestamp);
                                $this->db->where('mute', '0');
                                $feeding_owe_now = $this->db->get()->result_array();

                                $feeding_total_counter = 0;
                                $classes_total_counter = 0;
                                $transport_total_counter = 0;

                                foreach($feeding_owe_now as $row):

                                    //latest timestamp in the feeding table
                                    //$this->db->where('due >', 0);
                                    $this->db->where('student_id', $row['student_id']);
                                    $this->db->where('timestamp <=', $timestamp);
                                    $this->db->where('mute', '0');
                                    $this->db->order_by('timestamp', 'desc');
                                    $this->db->limit(1);
                                    $feeding_timestamp = $this->db->get('feeding_fee')->row()->timestamp;

                                    //latest timestamp in the transport fare table
                                    $this->db->where('due >', 0);
                                    $this->db->where('student_id', $row['student_id']);
                                    $this->db->where('timestamp <=', $timestamp);
                                    $this->db->where('mute', '0');
                                    $this->db->order_by('timestamp', 'desc');
                                    $this->db->limit(1);
                                    $transport_timestamp = $this->db->get('transport_fare')->row()->timestamp;


                                    $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;

                                    $year = $this->db->get_where('feeding_fee', array('student_id' => $row['student_id'], 'timestamp' => $timestamp))->row()->year;
                                    /*$month = $this->db->get_where('feeding_fee', array('student_id' => $row['student_id'], 'timestamp' => $timestamp))->row()->month;*/

                                    $class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0', 'year' => $year))->row()->class_id;

                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;

                                    $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                                    $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                    $class = '';
                                    if($class_name == 'CRECHE') {
                                        $class = $class_name;
                                    } else {
                                        $class = $class_name. ' '. $class_name_numeric.$sec_name;
                                    }
                                  
                                    //if($amount > 0) {
                                        //feeding owe
                                        $this->db->select_sum('due');
                                        $this->db->from('feeding_fee');
                                        $this->db->where('due >', 0);
                                        $this->db->where('student_id', $row['student_id']);
                                        $this->db->where('timestamp', $feeding_timestamp);
                                        $this->db->where('mute', '0');
                                        $tf_owe = $this->db->get()->row()->due;
                                  /*  } else if($amount < 0) {
                                        //feeding owe
                                        $this->db->select_sum('due');
                                        $this->db->from('feeding_fee');
                                        $this->db->where('student_id', $row['student_id']);
                                        $this->db->where('timestamp', $timestamp);
                                        $this->db->where('due <', 0);
                                        $tf_owe = $this->db->get()->row()->due;
                                    }*/

                                   // if($amount > 0) {
                                        //classes owe
                                        $this->db->select_sum('cdue');
                                        $this->db->from('feeding_fee');
                                        $this->db->where('cdue >', 0);
                                        $this->db->where('student_id', $row['student_id']);
                                        $this->db->where('timestamp', $feeding_timestamp);
                                        $this->db->where('mute', '0');
                                        $tc_owe = $this->db->get()->row()->cdue;
                                  /*  } else if($amount < 0) {
                                        //classes owe
                                        $this->db->select_sum('cdue');
                                        $this->db->from('feeding_fee');
                                        $this->db->where('student_id', $row['student_id']);
                                        $this->db->where('timestamp', $timestamp);
                                        $this->db->where('cdue <', 0);
                                        $tc_owe = $this->db->get()->row()->cdue;
                                    }*/

                                   //if($amount > 0) {
                                        //transport owe
                                        $this->db->select_sum('due');
                                        $this->db->from('transport_fare');
                                        $this->db->where('due >', 0);
                                        $this->db->where('student_id', $row['student_id']);
                                        $this->db->where('timestamp', $transport_timestamp);
                                        $this->db->where('mute', '0');
                                        $tt_owe = $this->db->get()->row()->due;
                                   /* } else if($amount < 0) {
                                        //transport owe
                                        $this->db->select_sum('due');
                                        $this->db->from('transport_fare');
                                        $this->db->where('student_id', $row['student_id']);
                                        $this->db->where('timestamp', $timestamp);
                                        $this->db->where('due <', 0);
                                        $tt_owe = $this->db->get()->row()->due;
                                    }*/

                                    if($tf_owe != 0 || $tc_owe != 0 || $tt_owe != 0) {
                                    ?>
                        <tr>
                            <td><?php echo $this->db->get_where('student' , array(
                                    'student_id' => $row['student_id']
                                ))->row()->student_code;?></td>

                            <td><img src="<?php echo $this->crud_model->get_image_url('student', $row['student_id'], $gender);?>" class="img-circle" width="40" /></td>
                            <td class="al">
                                <?php
                                    echo $this->db->get_where('student' , array(
                                        'student_id' => $row['student_id']
                                    ))->row()->name;
                                ?>
                            </td>
                            <td class="al">
                                <?php
                                    echo $this->db->get_where('student' , array(
                                        'student_id' => $row['student_id']
                                    ))->row()->address;
                                ?>
                            </td>
                            
                            <td class="al">
                                <?php
                                $parent_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->parent_id;
                                    echo $this->db->get_where('parent' , array(
                                        'parent_id' => $parent_id
                                    ))->row()->phone;
                                ?>
                            </td> 

                            <td>
                                <?= $class; ?>
                            </td>

                        <td class="ar">
                            <?php 
                                
                                echo number_format($tf_owe, 2, '.', ',');
                            ?>
                        </td>
                        <td class="ar">
                            <?php 
                                
                                echo number_format($tc_owe, 2, '.', ',');
                            ?>
                        </td>
                        <td class="ar">
                            <?php 
                                
                                echo number_format($tt_owe, 2, '.', ',');
                            ?>
                        </td>
                            
                        </tr>
                        <?php 
                        }

                        $feeding_total_counter += $tf_owe;
                        $classes_total_counter += $tc_owe;
                        $transport_total_counter += $tt_owe; 
                    endforeach;
                        ?>
                    </tbody>
                </table>

                <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" >
                    <thead>
                        <tr>
                            <th colspan="6"></th>
                            <th style="text-align: right">FEEDING FEE</th>
                            <th style="text-align: right">CLASSES FEE</th>
                            <th style="text-align: right">TRANSPORT FARE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6"><strong>Total:</strong></td>
                            <td style="text-align: right"><strong><?=number_format($feeding_total_counter, 2, '.', ','); ?></strong></td>
                            <td style="text-align: right"><strong><?=number_format($classes_total_counter, 2, '.', ','); ?></strong></td>
                            <td style="text-align: right"><strong><?=number_format($transport_total_counter, 2, '.', ','); ?></strong></td>
                        </tr>
                    </tbody>
                </table>

            </div>
        
        </div>


            </div>
        </div>
    </div>
</div>

    
<script type="text/javascript">

    jQuery(document).ready(function($)
    {
        $('#mark_print').dataTable();
        var elem = $('#print');
        //PrintElem(elem);
        //Popup(data);

    });

   
 function PrintElem(elem)
    {   

        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', '', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/js/datatables/responsive/css/datatables.responsive.css'); ?>" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>">');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>"/>');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>" type="text/css" />');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);

        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>" />');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/js/bootstrap.js'); ?>" />');
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }

    }
</script>
