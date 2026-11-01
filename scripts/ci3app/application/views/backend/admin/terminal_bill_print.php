
<!doctype html>
<html>
    <head>

        <link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive_table.css');?>">

    </head>
    <body>  

        <div class="container">
            <div class="row" style="padding-top: 15px;">
                <a onClick="PrintElem('#print')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
                       Print Report Sheet
                        <i class="glyphicon glyphicon-print"></i>
                </a>
            </div><hr>
            <div class="row">
                <div class="col-lg-12">
                    <div id="print">

                    <?php

                    $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
                    $running_year       =   $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
                    $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
                    $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
                    $term_ending        =   $this->db->get_where('settings' , array('type'=>'term_ending'))->row()->description;
                    $next_term_begins   =   $this->db->get_where('settings' , array('type'=>'next_term_begins'))->row()->description;
                    $running_term       =   $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;



                     $data_array2 =  array(
                        'class_id' => $class_id, 
                            'section_id' => $section_id, 
                                'year' => $running_year, 
                                        'term' => $running_term
                                );

                        $this->db->select('*');
                        $this->db->where($data_array2);
                        $this->db->where('mute', '0');
                        $this->db->from('enroll');
                        $students_array = $this->db->get()->result_array();

                        foreach($students_array as $student_row):

                                $student_id = $student_row['student_id'];

                        $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
                        $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

                        
                        //incase the admin did not set correct sem ending or term ending, let's use current date
                        $current_date = strtotime(date('d-m-Y'));
                        //for term
                        if(strtotime($term_ending) < $current_date) {
                            $term_ending = date('d-m-Y');
                        }
                        



                        $grading_e = $this->db->get('grade')->result_array();

                        $gender = $this->db->get_where('student', array('student_id' => $student_id))->row()->sex;


                        //Account section
                        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
                        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

                        $next_term = $running_term + 1;
                        $next_year = $running_year;

                        if($next_term == 4) {
                            $next_term = 1;

                            $p1 = explode('-', $running_year)[0] + 1;
                            $p2 = explode('-', $running_year)[1] + 1;

                            $next_year = $p1 .'-'. $p2;
                        }

                        //feeding and classes owe
                        $this->db->select('timestamp');
                        $this->db->from('feeding_fee');
                        $this->db->where('student_id', $student_id);
                        //$this->db->where('year', $running_year);
                        //$this->db->where('term', $running_term);
                        $this->db->order_by('timestamp', 'desc');
                        $this->db->limit(1);
                        $previous_day_timestamp = $this->db->get()->row()->timestamp; //selecting just the previously entered timestamp for this particular class


                        //Arrears
                        //invoices owe
                        $this->db->where('can_delete !=', 'trash');
                        $invoices_owe_row =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($term_ending)))->num_rows();

                        $this->db->where('can_delete !=', 'trash');
                        $invoices_owe =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($term_ending)))->result_array();


                        //feeding fee owe
                        /*$feeding_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
                        $feeding_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;

                        //classes fee owe
                        $classes_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->num_rows();
                        $classes_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->row()->cdue;

                        //transport fee owe
                        $transport_owe_row =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
                        $transport_owe =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;*/

                        $feeding_owe_row = 0;
                        $classes_owe_row = 0;
                        $transport_owe_row = 0;
                        //arrears end

                        //Next term bills
    
                        //invoices owe for next term
                        $this->db->where('can_delete !=', 'trash');
                        $invoices_next_term_row =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->num_rows();

                        $this->db->where('can_delete !=', 'trash');
                        $invoices_next_term =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->result_array();

                        //promotion
                        $promoted_to = '';
                        if($running_term == 3) {
                            $explode_running_year = explode('-', $running_year);
                            $year_part1 = $explode_running_year[0] + 1;
                            $year_part2 = $explode_running_year[1] + 1;

                            $promoted_to_year = $year_part1. '-'. $year_part2;
                            $promoted_to_term = 1;

                            $promoted_to_class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'term' => $promoted_to_term))->row()->class_id;
                            $promoted_to_class = $this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row()->name. ' '.$this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row()->name_numeric;
                        }else{
                            $promoted_to_class = 'N/A';
                        }
                    ?>

                        <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>
                        <style type="text/css">
                             #mark_print th, #grade_table th, #conducts th, .creche th, .info_st {
                                background-color: grey;
                                border: 2px solid #e2dddd;
                                padding: 5px;
                                color: white;
                                border-bottom: 2px solid red;
                             }

                             .info_st {
                                background-color: grey;
                                border: 1px solid #e2dddd;
                                padding: 3px;
                                color: white;
                                border-bottom: 2px solid #000000;
                             }

                            td {
                                padding: 5px;
                            }

                            #grade_table tbody tr td{
                                line-height: 25px;
                            }

                            #term{
                                background-color: black; 
                                padding: 5px 15px 5px 15px; 
                                border-radius: 9px;
                                font-size: 24px;
                                letter-spacing: 5px;
                                color: #ffffff;

                            }

                            .account_tb th {
                                background-color: grey;
                                border: 1px solid #e2dddd;
                                padding: 5px;
                                color: white;
                                border-bottom: 1px solid red;
                                text-align: left;
                             }


                            div #photo_passport {
                                margin-top: -20px;
                                border-radius: 16% 6%;
                            }


                            @media Print{
                                page {
                                background: #ffffff;
                                display: block;
                                margin: 0 auto;
                                margin-bottom: 0.5cm;
                                box-shadow: 0;
                                page-break-after: always;
                                position: relative;

                                }

                                page[size="A5"] {
                                    width: 5.83in;
                                    height: 21cm;
                                }

                                body, page {
                                    margin: 0;
                                    box-shadow: 0;
                                }

                                div #logo{
                                    position: absolute;
                                    top: 82px;
                                    left: 10px;
                                }

                                div #photo_passport{
                                    position: absolute;
                                    top: 91px;
                                    right: 10px;

                                }

                                #top_row{
                                    margin-top: -55px;
                                }

                                #student_details p{
                                    line-height: 30px;
                                }

                                #promote{
                                    margin-top: -32px;
                                }

                                #box{
                                    margin-top: -10px;
                                }

                                #gh{
                                    margin-top: -15px;
                                }

                                .sign {
                                    position: fixed;
                                    bottom: 12px;
                                }

                                #container {
                                    height: 740px;
                                }

                                
                            }
                        </style>
                <page size="A5" style="font-size: 16px">
                    <div>                        
                        <center>  
                            <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px; margin-top: -5px"><?php echo $system_name;?></h3>
                        </center>
                
                            <!-- <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                               <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 80px;"><br> 
                            </div> -->
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <center>
                                <h4 align='center' id="add"><?php echo $location;?></h4>
                                <h4 align='center' id="box"><?php echo $address;?></h4>
                                <span align="center" id="term">TERMINAL BILLS</span>

                                <!--Either Upper or Lower Primary or JHS-->
                                <?php 
                                    $level = '';

                                    if($class_name == 'CLASS' && $class_name_numeric < 4){
                                        $level = 'LOWER PRIMARY';
                                    }elseif ($class_name == 'CLASS' && $class_name_numeric >= 4) {
                                        $level = 'UPPER PRIMARY';
                                    }elseif ($class_name == 'FORM') {
                                        $level = 'JHS';
                                    }elseif ($class_name == 'CRECHE' || $class_name == 'NURSERY' || $class_name == 'KG') {
                                        $level = 'PRESCHOOL';
                                    }
                                ?>

                                <?php
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }
                                ?>

                                <h4 align='center' style="letter-spacing: 6px;"><?php echo $level;?></h4>
                            </div>
                            </center>
                            <!-- <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <img src="<?php echo $this->crud_model->get_image_url('student',$student_id, $gender);?>" class="img-circle" id="photo_passport" width="80" height="80" />
                            </div> -->                         
 
                          <hr>
                    </div>                     

 

            <!--Account-->

            <hr>
            <!--ACCOUNTS SECTION-BILLING-->
            <div class="row" style="margin-top: 30px">
                <table style="width: 100%">

                    <caption><u><h3>ACCOUNTS AND BILLING FOR <?=strtoupper(strtolower($this->db->get_where('student' , array('student_id' => $student_id))->row()->name));?></h3></u></caption>

                    <tbody>
                        <tr>
                            
                            <td width="50%" align="right" valign="top" id="arrears_tb">
                                <!--Account Section here (arrears)-->
                                <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse;border: 1px solid #000; font-size: 16px" border="1">
                                     <thead>
                                        <tr>
                                            <th>ITEM</th>
                                            <th style="text-align: right">AMOUNT</th>
                                        </tr>
                                        <tr>
                                            <th colspan="2">PREVIOUS ARREARS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        //Invoices owe (arrears)
                                        $invoice_arrears_total = 0;

                                        $rows_counter = 0;

                                        if($invoices_owe_row > 0):
                                            foreach($invoices_owe as $inv_row):



                                                //show only if it is not next term and next years' bill
                                                if($inv_row['term'] != $next_term || $inv_row['year'] != $next_year) {
                                        

                                                    $rows_counter++; //increment

                                                    $exp = explode('-',$inv_row['year']);
                                        ?>
                                        <tr>
                                            <td><?= $inv_row['title'].' <strong>[Term|Year: '.$inv_row['term'].'|'.$exp[1].']</strong>'; ?></td>
                                            <td align="right"><?= numfmt_format_currency($fmt, $inv_row['due'], $currency); ?></td>
                                        </tr>
                                        <?php 
                                            $invoice_arrears_total = $invoice_arrears_total + $inv_row['due'];
                                                } //end of 'if' test to see if the selected term and year is not the same as the next term and year respectively
                                            endforeach;
                                        endif;
                                        //Feeding, Classes and Transport in arrears
                                            if($feeding_owe_row > 0):
                                        ?>
                                        <tr>
                                            <td>FEEDING FEE</td>
                                            <td align="right"><?= numfmt_format_currency($fmt, $feeding_owe, $currency); ?></td>
                                        </tr>
                                        <?php 
                                            endif;
                                            if($classes_owe_row > 0):
                                        ?>
                                        <tr>
                                            <td>CLASSES FEE</td>
                                            <td align="right"><?= numfmt_format_currency($fmt, $classes_owe, $currency); ?></td>
                                        </tr>
                                        <?php 
                                            endif;
                                            if($transport_owe_row > 0):
                                        ?>
                                        <tr>
                                            <td>TRANSPORT FARE</td>
                                            <td align="right"><?= numfmt_format_currency($fmt, $transport_owe, $currency); ?></td>
                                        </tr>
                                        <?php 
                                            endif;

                                            $arrears_total = $invoice_arrears_total + $feeding_owe + $classes_owe + $transport_owe;
                                        ?>
                                        <tr>
                                            <th>SUB-TOTAL</th>
                                            <td align="right"><strong><?= numfmt_format_currency($fmt, $arrears_total, $currency); ?></strong></td>
                                        </tr>

                                        <?php
                                            if($rows_counter == 0 && $feeding_owe_row == 0 && $classes_owe_row == 0 && $transport_owe_row == 0) {
                                                echo '<tr><td colspan="2">No Arrears Found!</td></tr>';
                                            }
                                        ?>

                                     </tbody>
                                </table>
                            </td>
                            <td width="50%" align="right" valign="top">
                                <!--Account Section here (next term billing)-->
                                <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse;border: 1px solid #000; font-size: 16px" border="1">
                                     <thead>
                                        <tr>
                                            <th>ITEM</th>
                                            <th style="text-align: right">AMOUNT</th>
                                        </tr>
                                        <tr>
                                            <th colspan="2">NEXT TERM BILLS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        //Invoices owe (next term)
                                        $invoice_next_term_total = 0;

                                        if($invoices_next_term_row > 0) {
                                            foreach($invoices_next_term as $next_row):
                                        
                                        ?>
                                        <tr>
                                            <td><?= $next_row['title']; ?></td>
                                            <td align="right"><?= numfmt_format_currency($fmt, $next_row['due'], $currency); ?></td>
                                        </tr>
                                        <?php 
                                            $invoice_next_term_total = $invoice_next_term_total + $next_row['due'];
                                            endforeach;

                                            } else {
                                                echo '<tr><td colspan="2">No Bills Found For Next Term</td></tr>';
                                            }
                                        ?>
                                        <tr><th>SUB-TOTAL</th><td align="right"><strong><?= numfmt_format_currency($fmt, $invoice_next_term_total, $currency); ?></strong></td></tr>

                                     </tbody>
                                </table>

                                 <!--Summation of the arrears and the bills for next term-->
                                <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse;border: 1px solid #000; font-size: 16px" border="1">
                                     <thead>
                                        <tr>
                                            <th>ARREARS</th>
                                            <th>BILLS</th>
                                            <th>TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?= numfmt_format_currency($fmt, $arrears_total, $currency); ?></td>
                                            <td><?= numfmt_format_currency($fmt, $invoice_next_term_total, $currency); ?></td>
                                            <td><strong><?= numfmt_format_currency($fmt, $arrears_total + $invoice_next_term_total, $currency); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div> <!--end of account section div-->

            <div style="position: fixed; right: 5px; bottom: 10px; text-align: right;" class="pull-right">
                <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse; font-size: 16px">

                        <tbody align="right">
                            <tr>
                                <td>ACADEMIC YEAR:</td>
                                <td><?php echo $running_year;?></td>
                            </tr>

                            <tr>
                                <td>TERM:</td>
                                <td><?php echo $running_term;?></td>
                            </tr>

                            <tr>
                                <td>TERM ENDING:</td>
                                <td><?php $date = date_create($term_ending); echo date_format($date, 'd-m-Y'); ?></td>
                            </tr>

                            <tr>
                                <td>NEXT TERM BEGINS:</td>
                                <td><?php $date = date_create($next_term_begins); echo date_format($date, 'd-m-Y'); ?></td>
                            </tr>
                        </tbody>
                    </table>
            </div>
            
            </page> <!-- ACCOUNTS END -->   

                        <?php endforeach; ?>

                    </div>
                 </div>
            </div>
        </div>
    

    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>    
    
<script type="text/javascript">
    jQuery(document).ready(function($)
    {

        
        var elem = $('#print');
        PrintElem(elem);
        Popup(data);

    });
    
    $('input[type="checkbox"]').click(function() {
        return false;
    });

   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'REPORT SHEET', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body style="font-size: 16px" >');
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

