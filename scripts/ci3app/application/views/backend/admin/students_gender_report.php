<!doctype html>
<html>
    <head>
        <title><?=get_settings('system_name');?> STUDENTS GENDER REPORT FOR <?=$running_year.'/'.$running_term?></title>
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

        <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>

         <script src="<?php echo base_url(); ?>assets/cdn/js/dataTables.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/dataTables.buttons-3.0.2.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.dataTables-3.2.4.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/jszip.min.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/pdfmake.min.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/vfs_fonts.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.html5.min.js" crossorigin="anonymous"></script>
         <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.colVis.min.js" crossorigin="anonymous"></script>




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
    
    <!-- Filters Section -->
    <div class="row" style="padding: 15px 0; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
        <div class="col-md-12">
            <form method="GET" action="<?php echo site_url('admin/students_gender_report'); ?>" id="filter_form" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: 600; margin-bottom: 5px; display: block;">Academic Year</label>
                    <select name="year" class="form-control" style="height: 40px;">
                        <?php
                        $years = $this->db->distinct()->select('year')->order_by('year', 'DESC')->get('enroll')->result_array();
                        foreach($years as $y):
                        ?>
                        <option value="<?php echo $y['year']; ?>" <?php echo ($y['year'] == $running_year) ? 'selected' : ''; ?>>
                            <?php echo $y['year']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: 600; margin-bottom: 5px; display: block;">Term</label>
                    <select name="term" class="form-control" style="height: 40px;">
                        <option value="1" <?php echo ($running_term == '1') ? 'selected' : ''; ?>>Term 1</option>
                        <option value="2" <?php echo ($running_term == '2') ? 'selected' : ''; ?>>Term 2</option>
                        <option value="3" <?php echo ($running_term == '3') ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary" style="height: 40px; padding: 0 30px;">
                        <i class="glyphicon glyphicon-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row" style="padding-top: 15px;">
        <a onClick="exportToExcel()" class="btn btn-success btn-icon icon-left hidden-print pull-right" style="margin-left: 10px;">
               <i class="glyphicon glyphicon-download-alt"></i>
               Export to Excel
        </a>
        <a onClick="PrintElem('#print')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
               <i class="glyphicon glyphicon-print"></i>
               Print Gender Report
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

                    td {
                        padding: 5px;
                        text-align: center;
                    }

                    .al {
                        text-align: left !important;
                    }
               
                </style>

                <center>
                    <h3><?=get_settings('system_name');?></h3>
                    <h4><u>STUDENTS GENDER REPORT</u></h4>
                    <p style="margin: 10px 0; font-size: 14px; color: #666;">
                        <strong>Academic Year:</strong> <?=$running_year;?> | <strong>Term:</strong> <?=$running_term;?>
                    </p>
                </center>
                
                <div class="tab-content">
                <div class="tab-pane active" id="home">

                    <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" id="students_print">
                        <thead>
                            <tr>
                                <th style="text-align: center"><div>CLASS</div></th>
                                <th style="text-align: center"><div>GENDER</div></th>
                                <th style="text-align: center"><div>TOTAL</div></th>   
                            </tr>
                            <tr>
                                <th></th>
                                <th >
                                    <table style="border-collapse:collapse;" width="100%" class="table table-bordered table-striped table-hover table-active">
                                        <thead>
                                            <tr>
                                                <th width="50%" style="text-align: center"><div>MALE</div></th>
                                                <th width="50%" style="text-align: center"><div>FEMALE</div></th>
                                                <!-- <th style="text-align: center"><div>UNKNOWN</div></th> -->
                                            </tr>
                                        </thead>
                                    </table>
                                </th>
                                <th></th>   
                            </tr>
                        </thead>
                        <tbody>
            <?php
            $allClassesIds = getAllClassList();
            $grand_total = 0;
            $grand_female = 0;
            $grand_male = 0;
            $grand_unknown = 0;

            for($i=0; $i < sizeof($allClassesIds); $i++):

                $n_female = 0;
                $n_male = 0;
                $n_unknown = 0;

               $students_ids_array = $this->crud_model->getStudentsIdsByClass($allClassesIds[$i], $running_year, $running_term);

               if(count($students_ids_array) < 1) continue;

               $students_ids = array_column($students_ids_array, 'student_id');

               /*male count*/
               $this->db->where_in('student_id', $students_ids);
               $this->db->where('sex', 'Male');
               $n_male = $this->db->get_where('student')->num_rows();

               /*female count*/
               $this->db->where_in('student_id', $students_ids);
               $this->db->where('sex', 'Female');
               $n_female = $this->db->get_where('student')->num_rows();

               /*unknown count*/
               $this->db->where_in('student_id', $students_ids);
               $this->db->where_not_in('sex', ['Male', 'Female']);
               $n_unknown = $this->db->get_where('student')->num_rows();

               /*summation*/
               $grand_male += $n_male;
               $grand_female += $n_female;
               $grand_unknown += $n_unknown;

               ?>
                <tr>
                    <td align="left" class="class-name"><?=getFullClassName($allClassesIds[$i]); ?></td>
                    <td class="gender-data">
                        <table border="1" style="border-collapse:collapse; border-color: #adadad;" width="100%" class="">
                            <thead>
                                <tr>
                                    <td width="50%" style="text-align: center" class="male-count"><div><?= number_format($n_male, 0, '.', ',');?></div></td>
                                    <td width="50%" style="text-align: center" class="female-count"><div><?= number_format($n_female, 0, '.', ',');?></div></td>
                                   <!--  <td style="text-align: center"><div><?= number_format($n_unknown, 0, '.', ',');?></div></td> -->
                                </tr>
                            </thead>
                        </table>
                    </td>
                    <td style="text-align: center" class="total-count"><div><?= number_format($n_male + $n_female, 0, '.', ',');?></div></td>
                </tr>
               <?php

               $grand_total += $n_male + $n_female;

            endfor;?>

                    <tr>
                        <td><strong>TOTAL:</strong></td>
                        <td >
                            <table style="border-collapse:collapse;" width="100%" class="">
                                <thead>
                                    <tr>
                                        <td width="50%" style="text-align: center; border-right: 1px solid #000;"><strong><?= number_format($grand_male, 0, '.', ',');?></strong></td>
                                        <td width="50%" style="text-align: center"><strong><?= number_format($grand_female, 0, '.', ',');?></strong></td>
                                       <!--  <td style="text-align: center"><strong><?= number_format($grand_unknown, 0, '.', ',');?></strong></td> -->
                                    </tr>
                                </thead>
                            </table>
                        </td>
                        <td><strong><?=$grand_total;?></strong></td>
                    </tr>
                    
                    
                        </tbody>
                    </table>
                </div>   
            </div>
            </div>
        </div>
    </div>
</div>



<script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
<script src="<?php echo base_url('assets/datatables/datatables.min.js');?>" type="text/javascript"></script>
</body>
</html>    
    
<script type="text/javascript">

    $(function($) {
        $('#all_male').text('<?= number_format($n_male, 0, '.', ',') ?>');
        $('#all_female').text('<?= number_format($n_female, 0, '.', ',') ?>');
        $('#all_unknown').text('<?= number_format($n_unknown, 0, '.', ',') ?>');

       /* $.fn.dataTable.ext.errMode = 'none';
        $('#students_print').DataTable({
           // bFilter: false,
            bPaginate: false,
            layout: {
                topStart: {
                    buttons: [
                        'colvis',
                        {
                            extend: 'copyHtml5',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        
                    ]
                }
            }
        });*/
    })
   
 function PrintElem(elem)
    {
        var printWindow = window.open('', 'Print Gender Report', 'height=800,width=1200');
        printWindow.document.write('<!DOCTYPE html>');
        printWindow.document.write('<html><head><title><?=get_settings('system_name');?> STUDENTS GENDER REPORT FOR <?=$running_year."/".$running_term?></title>');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
        printWindow.document.write('th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }');
        printWindow.document.write('th { background-color: #666; color: white; font-weight: bold; }');
        printWindow.document.write('.al { text-align: left !important; }');
        printWindow.document.write('h3, h4 { text-align: center; margin: 10px 0; }');
        printWindow.document.write('h4 { text-decoration: underline; }');
        printWindow.document.write('@media print { body { margin: 10px; } }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write($(elem).html());
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // Wait for content to load before printing
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }, 500);
    }

function exportToExcel() {
    // Create workbook and worksheet
    var wb = XLSX.utils.book_new();
    var ws_data = [];
    
    // Header section - centered across all columns
    ws_data.push(['<?=get_settings('system_name');?>', '', '', '']);
    ws_data.push(['STUDENTS GENDER REPORT', '', '', '']);
    ws_data.push(['Academic Year: <?=$running_year."/".$running_term?>', '', '', '']);
    ws_data.push(['Generated: ' + new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString(), '', '', '']);
    ws_data.push(['', '', '', '']);
    ws_data.push(['', '', '', '']);
    
    // Column headers
    ws_data.push(['CLASS', 'MALE', 'FEMALE', 'TOTAL']);
    
    // Get all data rows
    $('#students_print tbody tr').each(function() {
        var $row = $(this);
        var cells = $row.find('> td');
        
        if(cells.length === 3) {
            var firstCell = $(cells[0]).text().trim();
            
            if(firstCell.toUpperCase().includes('TOTAL')) {
                return;
            }
            
            var className = firstCell;
            var maleCount = $(cells[1]).find('.male-count').text().trim();
            var femaleCount = $(cells[1]).find('.female-count').text().trim();
            var total = $(cells[2]).text().trim();
            
            if(className && maleCount && femaleCount && total) {
                ws_data.push([className, maleCount, femaleCount, total]);
            }
        }
    });
    
    ws_data.push(['', '', '', '']);
    
    // Add TOTAL row
    $('#students_print tbody tr').each(function() {
        var $row = $(this);
        var cells = $row.find('> td');
        
        if(cells.length === 3) {
            var firstCell = $(cells[0]).text().trim();
            
            if(firstCell.toUpperCase().includes('TOTAL')) {
                var totalMale = $(cells[1]).find('td').eq(0).text().trim();
                var totalFemale = $(cells[1]).find('td').eq(1).text().trim();
                var grandTotal = $(cells[2]).text().trim();
                
                ws_data.push(['GRAND TOTAL', totalMale, totalFemale, grandTotal]);
            }
        }
    });
    
    ws_data.push(['', '', '', '']);
    ws_data.push(['Report generated by <?=get_settings('system_name');?> School Management System', '', '', '']);
    
    // Create worksheet
    var ws = XLSX.utils.aoa_to_sheet(ws_data);
    
    // Set column widths
    ws['!cols'] = [
        {wch: 30}, // CLASS column
        {wch: 12}, // MALE column
        {wch: 12}, // FEMALE column
        {wch: 12}  // TOTAL column
    ];
    
    // Merge cells for headers
    if(!ws['!merges']) ws['!merges'] = [];
    ws['!merges'].push(
        {s: {r: 0, c: 0}, e: {r: 0, c: 3}}, // School name
        {s: {r: 1, c: 0}, e: {r: 1, c: 3}}, // Report title
        {s: {r: 2, c: 0}, e: {r: 2, c: 3}}, // Academic year
        {s: {r: 3, c: 0}, e: {r: 3, c: 3}}, // Generated date
        {s: {r: ws_data.length - 1, c: 0}, e: {r: ws_data.length - 1, c: 3}} // Footer
    );
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'Gender Report');
    
    // Generate Excel file and download
    XLSX.writeFile(wb, 'Students_Gender_Report_<?=$running_year?>_<?=$running_term?>.xlsx');
}
</script>

