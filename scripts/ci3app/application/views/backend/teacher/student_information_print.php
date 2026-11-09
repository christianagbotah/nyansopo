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

    </head>
    <body>  
<div class="container">                
<hr />
<div class="row" style="padding-top: 15px;">
    <a onClick="PrintElem('#print')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
           Print Report Sheet
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
                
                .row {
                        overflow: scroll !important;
                    }
            </style>

        <ul class="nav nav-tabs bordered">
            <li class="active">
                <a href="#home" data-toggle="tab">
                    <span class="visible-xs"><i class="glyphicon glyphicon-users"></i></span>
                    <span class="hidden-xs"><?php echo get_phrase('all_students').' in '. $this->db->get_where('class' , array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;?></span>
                </a>
            </li>
        <?php
            $query = $this->db->get_where('section' , array('class_id' => $class_id));
            if ($query->num_rows() > 0):
                $sections = $query->result_array();
                foreach ($sections as $row):
        ?>
        <?php endforeach;?>
        <?php endif;?>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="home">

                <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" id="mark_print">
                    <thead>
                        <tr>
                            <th width="80"><div><?php echo get_phrase('iD_no');?></div></th>
                            <th width="80" style="text-align: center"><div><?php echo get_phrase('photo');?></div></th>
                            <th><div><?php echo get_phrase('name');?></div></th>
                            <th class="span3"><div><?php echo get_phrase('address');?></div></th>
                            <th><div><?php echo get_phrase('email');?></div></th>
                            <th><div><?php echo get_phrase('guardian_contact');?></div></th>
                            <th><div><?php echo get_phrase('date_of_birth');?></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                                $class_name = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;

                                if($class_name == 'JHSS') {
                                    $students   =   $this->db->get_where('enroll' , array(
                                    'class_id' => $class_id , 'mute' => '0', 'year' => $running_year, 'sem' => $running_term_sem
                                ))->result_array();

                                } else {
                                    $students   =   $this->db->get_where('enroll' , array(
                                    'class_id' => $class_id , 'mute' => '0', 'year' => $running_year, 'term' => $running_term_sem
                                ))->result_array();
                                }

                                foreach($students as $row):
                                    $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;
                                    ?>
                        <tr>
                            <td><?php echo $this->db->get_where('student' , array(
                                    'student_id' => $row['student_id']
                                ))->row()->student_code;?></td>
                            <td><img src="<?php echo $this->crud_model->get_image_url('student',$row['student_id'], $gender);?>" class="img-circle" width="40" /></td>
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
                                    echo $this->db->get_where('student' , array(
                                        'student_id' => $row['student_id']
                                    ))->row()->email;
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
                                <?php
                                    $bdate =  $this->db->get_where('student' , array(
                                        'student_id' => $row['student_id']
                                    ))->row()->birthday;

                                    $bdate_create = date_create($bdate);

                                    $bdate_get = date_format($bdate_create, 'M d, Y');

                                    if($bdate != '' || $bdate != null) {
                                        echo $bdate_get;
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>

            </div>
        
        </div>


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

   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'Students List', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body >');
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
<script type="text/javascript">

    jQuery(document).ready(function($) {
        $('.datatable').DataTable();
    });

</script>
