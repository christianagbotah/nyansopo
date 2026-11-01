<!doctype html>
<html>
    <head>

    	<?php include '_al01i/v_l02i/backend/admin/reports/includes/includes_top.php';?>
        <style type="text/css">
            #loader {
                position: absolute;
                height: 100vh;
                width: 100vw;
                z-index: 1;
                background-color: rgba(0, 0, 0, 0.6);

            }
            #loader_text {
                margin-top: 50vh;
                z-index: 999;
                color: #fff;
                background-color: rgba(0,0,0,0.6);
                width: 400px;
                padding: 5px;
            }

         
        </style>
        <script type="text/javascript">

            /*document.onreadystatechange = function(e) {
                if(document.readyState !== 'complete') {
                    document.querySelector('body').style.visibility = 'hidden';
                    document.querySelector('#loader').style.visibility = 'visible';
                } else {
                    document.querySelector('#loader').style.display = 'none';
                    document.querySelector('body').style.visibility = 'visible';
                    $('.container').css('display', 'block');
                }
            }*/
        </script>

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
        <!--<div id="loader" >
            <div>
                <center>
                    <h1 id="loader_text">Loading please wait...</h1>
                </center>
            </div>
        </div>-->
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
                        <span class="hidden-xs"><?php echo get_phrase('all_active_students');?></span>
                    </a>
                </li>
                <div class="tab-content">
                <div class="tab-pane active" id="home">

                    <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" id="hubtel_data_table">
                        <thead>
                        	<tr>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                        		<th></th>
                                <th></th>
                        	</tr>
                            <tr>
                                <th><div>Name</div></th>
                                <th><div>Date of birth</div></th>
                                <th><div><?php echo get_phrase('Class');?></div></th>
                                <th><div>Student ID</div></th>
                                <th><div>Fees Owed</div></th>
                                <th><div>Father's Name</div></th>
                                <th><div>Father's Email</div></th>
                                <th><div>Father's phone number</div></th>
                                <th><div>Mother's Name</div></th>
                                <th><div>Mother's Email</div></th>
                                <th><div>Mother's phone number</div></th>
                                
                            </tr>
                        </thead>
                        <tbody>
            <?php
        $allClassesIds = getAllClassList();
            $grand_total = 0;


                $this->db->select('enroll.student_id, enroll.class_id, student_code');
                $this->db->distinct();
                $this->db->from('enroll');
                $this->db->where('year', $running_year);
                $this->db->where('term', $running_term);
                $this->db->where('enroll.mute', '0');   
                $this->db->order_by('student_code', 'asc'); 
                $this->db->join('student', 'student.student_id = enroll.student_id');                   
                $students_ids = $this->db->get()->result_array(); 

                    foreach ($students_ids as $row):?>

                        <?php


                            $section_id = $this->db->get_where('enroll' , array('student_id'=>$row['student_id'], 'class_id' => $allClassesIds[$i]))->row()->section_id;
                            $student_info = $this->crud_model->getStudentInfoById($row['student_id']);

                            $feeOwed = $this->financial_report_model->getFeeOwedByStudentId($row['student_id']);
                        ?>
                        <tr>
                            <td align="left">
                                <?=$student_info->name;?>
                            </td>
                            <td>
                                <?php
                                    $bdate =  $student_info->birthday;

                                    $bdate_create = date_create($bdate);

                                    $bdate_get = date_format($bdate_create, 'd/m/Y');

                                    if($bdate != '' || $bdate != null) {
                                        echo $bdate_get;
                                    }

              
                                ?>
                            </td> 
                            <td>
	                            <?=getFullClassName($row['class_id']); ?>
	                        </td> 

                            
                            <td><?php echo $student_info->student_code;?></td>
                            <td><?php echo number_format($feeOwed, 2, '.', ',');?></td>
                            
                             
                            <td class="al">
                                <?php
                                $parent_id = $student_info->parent_id;

                                    echo $this->db->get_where('parent' , array(
                                        'parent_id' => $parent_id
                                    ))->row()->name;
                                ?>
                            </td>
                            <td></td> 
                            <td class="al" width="50">
                                <?php
                                    echo $this->db->get_where('parent' , array(
                                        'parent_id' => $parent_id
                                    ))->row()->phone;
                                ?>
                            </td> 

                            <td></td> 
                            <td></td> 
                            <td></td> 
                            
                        </tr>

                        <?php 

                    endforeach;?>  
                    
                        </tbody>
                    </table>
                </div>   
            </div>
            </ul>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>

<?php include '_al01i/v_l02i/backend/admin/reports/includes/includes_bottom.php';?>
</html>    
    
<script type="text/javascript">

	

	new DataTable('#hubtel_data_table', {
		paging: false,
	    layout: {
	        topStart: {
	            buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
	        }
	    }
	});
   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'All Active Students', 'height=400,width=600');
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

