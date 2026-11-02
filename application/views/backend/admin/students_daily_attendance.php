
<!doctype html>
<html>
    <head>
        <title><?= $page_title; ?></title>
        <?php
            include(VIEWPATH . 'backend/includes_top.php');
        ?>
        
        <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>
        <style type="text/css">
             #attendance_print th, #grade_table th, #conducts th {
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
            #main {
                
                height: 70vh;
                max-height: 70vh;
               
            }

        </style>

    </head>
    <body class="p-8">  
    <div class="container p-8">                
    <hr />
    <div class="row col-md-12" style="padding-top: 15px;">
            <?php echo form_open(site_url('admin/students_att/t/search'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'date_change')); ?>
                    <div class="col-sm-4">
                      <div class="form-group">
                        <label  class="control-label col-sm-4"><?php echo get_phrase('select_date');?></label>
                        <div class="col-sm-8">
                          <input type="text" name="date_sel" id="date_sel" class="form-control datepicker max-w-lg" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y', $timestamp); ?>" placeholder="Date">
                        </div>
                      </div>
                    </div>

                    <div class="col-sm-3">
                      <div class="form-group">
                        <div class="col-sm-12">
                          <input type="submit" name="load" class="border-2 border-solid border-green-400 p-2 rounded-lg text-2xl font-bold hover:bg-green-200 hover:p-4 animate-bounce" value="Load">
                        </div>
                      </div>
                    </div>
                </form>
        </div>

                <a onClick="PrintElem('#main')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
                       Print Report Sheet
                        <i class="glyphicon glyphicon-print"></i>
                </a>
    </div>

    <hr class="border-2 border-solid border-gray-300">
    <br>

    <div class="row" id="main">
        <div class="col-md-12">
            <div class="tab-pane active" id="home">
                <div>
                    <center><div>Please wait... <i class="fa fa-spinner fa-pulse"></i></div></center>
                </div>
                
            </div>
        </div>
    </div>
</div>

 <?php
    include VIEWPATH . 'backend/includes_bottom.php';
?>
</body>
</html>    
    
<script type="text/javascript">

    jQuery(document).ready(function($) {

         $.ajaxSetup({
            //cache: false,
            data: {
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            } 
         }); 

        loadAttendanceData();
        $('#attendance_print').DataTable({
            bFilter: false,
            bPaginate: false,
            dom: 'Blfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: ':visible'
                    }
                },

                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'btn btn-danger',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                 {
                    extend: 'print',
                    text: 'PRINT',
                    exportOptions: {
                        columns: ':visible'
                    }
                 },
                'colvis',
            ]
        });
    });

    function loadAttendanceData(att_status) {

        $('#table_holder').html('<div class="text-center p-5">Please wait... <i class="fa fa-spinner fa-pulse"></i></div>')

        let timestamp = $('#date_sel').val();
        
        // Default to empty string if att_status is undefined
        if(typeof att_status === 'undefined') {
            att_status = '';
        }


        $.ajax({

            url: '<?=site_url('admin/getStudentsAttendance/');?>' + timestamp + '/' + att_status,
            type: 'post',
            dataType: 'json',
            cache: false,
        })
        .done(function(resp) {

            $('#home').html(resp.table);


        })
        .fail(function(err) {
            console.error(err.responseText);
        })
    }

    $('#date_change').submit(function(ev) {
        ev.preventDefault();
        loadAttendanceData();
    })
   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', '', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url();?>assets/css/neon-theme.css" type="text/css" />');

        
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url();?>assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url();?>assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></\\script><script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></\\script></body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }




</script>
