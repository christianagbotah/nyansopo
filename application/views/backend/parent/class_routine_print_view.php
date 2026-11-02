<?php
    $class_name    =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
    $section_name  =   $this->db->get_where('section' , array('section_id' => $section_id))->row()->name;
    $system_name   =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
    $running_year  =   $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
    $running_term  =   $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
    $student_name  =   $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
?>

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


    </head>
    <body>
<div class=" row col-md-12" style="margin-top: 20px;">
    <a onClick="PrintElem('#print')" id="print_btn" class="btn btn-default btn-icon icon-left hidden-print pull-right">
        Print Time Table
        <i class="entypo-print"></i>
    </a>

</div><br><br>

<div id="print">

    <script src="<?php echo base_url('assets/js/jquery-1.11.0.min.js'); ?>"></script>
    <style type="text/css">
        td {
            padding: 5px;
        }
    </style>

    <center>
        <img src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br>
        <h3 style="font-weight: 100;"><?php echo $system_name;?></h3>
        <strong><?php echo strtoupper(get_phrase('class_time_table_for:')).' '. $student_name;?></strong><br>
        <?php echo $class_name;?> : <?php echo get_phrase('section');?> <?php echo $section_name;?><br>
    </center>
    <br>
    <table style="width:100%; border-collapse:collapse;border: 1px solid #eee; margin-top: 10px;" border="1">
        <tbody>
            <?php 
                for($d=1;$d<=7;$d++):
                
                if($d==1)$day='sunday';
                else if($d==2)$day='monday';
                else if($d==3)$day='tuesday';
                else if($d==4)$day='wednesday';
                else if($d==5)$day='thursday';
                else if($d==6)$day='friday';
                else if($d==7)$day='saturday';
                ?>
                <tr>
                    <td width="100"><?php echo strtoupper($day);?></td>
                    
                            <td align="left">
                        <?php
                        $this->db->order_by("time_start", "asc");
                        $this->db->where('day' , $day);
                        $this->db->where('class_id' , $class_id);
                        $this->db->where('section_id' , $section_id);
                        $this->db->where('year' , $running_year);
                        $this->db->where('term' , $running_term);
                        $routines   =   $this->db->get('class_routine')->result_array();
                        foreach($routines as $row):
                        ?>
                            <div style="float:left; padding:8px; margin:5px; background-color:#ccc;">
                                <?php echo $this->crud_model->get_subject_name_by_id($row['subject_id']);?>
                                <?php
                                    if ($row['time_start_min'] == 0 && $row['time_end_min'] == 0) 
                                        echo '('.$row['time_start'].'-'.$row['time_end'].')';
                                    if ($row['time_start_min'] != 0 || $row['time_end_min'] != 0)
                                        echo '('.$row['time_start'].':'.$row['time_start_min'].'-'.$row['time_end'].':'.$row['time_end_min'].')';
                                ?>
                            </div>
                        <?php endforeach;?>
                            </td>

                    
                </tr>
                <?php endfor;?>
                </tbody>
           </table>

        <br>

</div>

<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>


<script type="text/javascript">
 // print invoice function
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
        var mywindow = window.open('CLASS TIME TABLE', 'my div', 'height=400,width=600');
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