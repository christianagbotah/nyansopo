<link rel="stylesheet" href="<?=base_url('node_modules/flowbite/dist/flowbite.min.css')?>">
<link rel="stylesheet" href="<?=base_url('node_modules/flowbite-datepicker/dist/css/datepicker.min.css')?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/app.v1.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/switch-buttons.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css');?>">
<link href="<?php echo base_url(); ?>assets/cdn/fonts/open-sans.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/responsive_table.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap-tagsinput.css');?>">
<link href="<?php echo base_url(); ?>assets/cdn/fonts/orbitron.css" rel="stylesheet">

<!-- Select2 v3.5.2 CSS (original version) -->
<link rel="stylesheet" href="<?php echo base_url('assets/js/select2/select2.css');?>">

<link rel="stylesheet" href="<?php echo base_url('assets/js/selectboxit/jquery.selectBoxIt.css');?>">

<!-- Tailwindcss -->
<link rel="stylesheet" href="<?php echo base_url('assets/tailwindcss/output.css');?>">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/mobile-responsive-fix.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/modal-z-index-fix.css">
<link rel="stylesheet" href="<?php echo base_url('assets/css/modern-scrollbar.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/modal-button-professional-styling.css'); ?>">

<?php
    $skin_colour = $this->db->get_where('settings' , array(
        'type' => 'skin_colour'
    ))->row()->description;
    if ($skin_colour != ''):?>
    <!--<link rel="stylesheet" href="<?php echo base_url('assets/css/skins/' . $skin_colour . '.css');?>"> -->

<?php endif;?>

<?php 
    $text_align_row = $this->db->get_where('settings', array('type' => 'text_align'))->row();
    $text_align = $text_align_row ? $text_align_row->description : 'left-to-right';
    if ($text_align == 'right-to-left') : 
?>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-rtl.css');?>">
<?php endif; ?>

<link rel="shortcut icon" href="<?php echo base_url('uploads/school_logo.png');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/font-awesomenew/css/fontawesome.min.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/fontawesome/6.7.2/css/all.min.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/js/vertical-timeline/css/component.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/js/wysihtml5/bootstrap-wysihtml5.css');?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/cdn/css/dataTables.dataTables.css');?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/cdn/css/buttons.dataTables.css');?>"/>

<!-- DataTables Layout Fix - Global Override -->
<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/datatables-layout-fix.css');?>?v=<?php echo time(); ?>"/>



<style>
    .form-check-inline .form-check-input, .form-horizontal .radio, .form-horizontal .checkbox {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        cursor: pointer;
    }
</style>


<!--FontAwesome-->
<!-- FontAwesome Kit removed for offline use - using local version below -->
<script src="<?php echo base_url('assets/fontawesome/6.7.2/js/all.min.js');?>" crossorigin="anonymous"></script>

<?php
// TASK 3.3: Disable Tailwind runtime for fee_collection_portal to prevent media query conflicts
$current_page = $this->router->fetch_method();
if ($current_page !== 'fee_collection_portal'):
?>
<script src="<?php echo base_url('assets/tailwindcss/tailwindcss.js');?>" type="text/javascript"></script>
<?php endif; ?>

<!--Amcharts - Conditional loading for dashboard/reports only (MAIN PERFORMANCE OPTIMIZATION) -->
<?php 
$chart_pages = ['dashboard', 'income_expenditure', 'monthly_collection_report', 'student_attendance_report'];
$needs_charts = in_array($current_page, $chart_pages);
if ($needs_charts):
?>
<script src="<?php echo base_url('assets/js/amcharts/amcharts.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/pie.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/serial.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/gauge.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/funnel.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/radar.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/exporting/amexport.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/exporting/rgbcolor.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/exporting/canvg.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/exporting/jspdf.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/exporting/filesaver.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/amcharts/exporting/jspdf.plugin.addimage.js');?>" type="text/javascript"></script>
<?php endif; ?>

<!--Chartjs-->
<script src="<?php echo base_url('assets/cdn/js/chart-3.9.1.min.js');?>" type="text/javascript"></script>

<!--Chart Helper - Global Chart Loop Prevention-->
<script src="<?php echo base_url('assets/js/chart-helper.js');?>" type="text/javascript"></script>

<!--number_format-->
<script src="<?php echo base_url('assets/js/number_format.js');?>" type="text/javascript"></script>

<!--jQuery-->
<script src="<?php echo base_url('assets/js/jquery-3.4.1.js');?>" type="text/javascript"></script>

<!-- CSRF Helper for AJAX requests -->
<script src="<?php echo base_url('assets/js/csrf-helper.js');?>" type="text/javascript"></script>

<!-- Include html2canvas and jsPDF libraries -->
<script src="<?php echo base_url('assets/js/pdf-plugins/html2canvas.min.js');?>"></script>
<script src="<?php echo base_url('assets/js/pdf-plugins/jspdf.umd.min.js');?>"></script>

<style type="text/css">
    /* .sidebar-menu a i{
        border: 1px solid #0275d8 !important; 
        padding: 5px !important;
    } */
    
    /* Modern slim scrollbars are now handled by modern-scrollbar.css */
</style>

<script type="text/javascript">

    
    function checkDelete()
    {
        showCustomConfirm("Are You Sure You Want To Delete This?", function() {
            return true;
        }, function() {
            return false;
        });
        // Return false to prevent default action until user confirms
        return false;
    }


</script>