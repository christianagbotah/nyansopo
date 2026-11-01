<?php
    $running_year = get_settings('running_year');
    $running_term = get_settings('running_term');
?>
<!doctype html>
<html>
    <head>
        <title><?=get_settings('system_name');?> TEACHERS GENDER REPORT FOR <?=$running_year.'/'.$running_term?></title>
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

        <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/cdn/css/dataTables.dataTables.css"/>
        <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/cdn/css/buttons.dataTables.css"/>

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

                <style type="text/css">
                     #mark_print th, #grade_table th, #conducts th {
                        background-color: grey;
                        border: 2px solid #e2dddd;
                        padding: 5px;
                        color: white;
                        border-bottom: 2px solid red;
                     }

               
                </style>

                <center>
                    <h3><?=get_settings('system_name');?></h3>
                    <h4><u>TEACHERS GENDER REPORT</u></h4>
                </center>
                
                <div class="tab-content">
                <div class="tab-pane active" id="home">

                    <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" id="teachers_print">
                        <thead>
                            <tr>
                                <th><div style="font-size: 11px">MALE: <strong id="totalMales"></strong></div></th>
                                <th><div style="font-size: 11px">FEMALE: <strong id="totalFemales"></strong></div></th> 
                                <th><div style="font-size: 11px">GENDER TOTAL: <strong id="total"></strong> | TOTAL TEACHERS: <strong id="totalTeachers"></strong></div></th>  
                            </tr>
                            <tr>
                                <th><div>NAME</div></th>
                                <th style="text-align: center"><div>PHONE</div></th>
                                <th style="text-align: center"><div>GENDER</div></th>   
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $allActiveTeachers = $this->crud_model->getAllActiveTeachers();


                            $grand_total = 0;
                            $grand_female = 0;
                            $grand_male = 0;
                            $grand_unknown = 0;
                            $totalParents = 0;
                            

                            foreach($allActiveTeachers as $row):

                                if($row['sex'] == 'Male') $grand_male ++;
                                if($row['sex'] == 'Female') $grand_female ++;

                                $totalParents++;

                               ?>
                                <tr>
                                    <td align="left"><?= $row['name']; ?></td>
                                    <td align="center"><?= $row['phone']; ?></td>
                                    <td align="center"><?= $row['sex']; ?></td>
                                </tr>
                               <?php

                            endforeach;?>

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
<script src="<?php echo base_url('assets/datatables/datatables.min.js');?>" type="text/javascript"></script>


 <script src="<?php echo base_url(); ?>assets/cdn/js/dataTables.js" crossorigin="anonymous"></script>
 <script src="<?php echo base_url(); ?>assets/cdn/js/dataTables.buttons-3.0.2.js" crossorigin="anonymous"></script>
 <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.dataTables-3.2.4.js" crossorigin="anonymous"></script>
 <script src="<?php echo base_url(); ?>assets/cdn/js/jszip.min.js" crossorigin="anonymous"></script>
 <script src="<?php echo base_url(); ?>assets/cdn/js/pdfmake.min.js" crossorigin="anonymous"></script>
 <script src="<?php echo base_url(); ?>assets/cdn/js/vfs_fonts.js" crossorigin="anonymous"></script>
 <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.html5.min.js" crossorigin="anonymous"></script>
 <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.colVis.min.js" crossorigin="anonymous"></script>
</body>
</html>    
    
<script type="text/javascript">

    $(function($) {
        $('#totalMales').text('<?= number_format($grand_male, 0, '.', ',') ?>');
        $('#totalFemales').text('<?= number_format($grand_female, 0, '.', ',') ?>');
        $('#total').text('<?= number_format($grand_male + $grand_female, 0, '.', ',') ?>');
        $('#totalTeachers').text('<?= number_format($totalParents, 0, '.', ',') ?>');

        $.fn.dataTable.ext.errMode = 'none';
        $('#teachers_print').DataTable({
            bFilter: false,
            bPaginate: false,
            info: false,
            /*layout: {
                topStart: {
                    buttons: [
                        'colvis',
                        'print',
                        {
                            extend: 'pdfHtml5',
                            exportOptions: 'visible'
                        },
                        {
                            extend: 'excelHtml5',
                            exportOptions: 'visible'
                        }
                        
                    ]
                }
            }*/
        });
    })
   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', '<?=get_settings('system_name');?> STUDENTS GENDER REPORT FOR <?=$running_year.'/'.$running_term?>', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title><?=get_settings('system_name');?> STUDENTS GENDER REPORT FOR <?=$running_year.'/'.$running_term?></title>');
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

