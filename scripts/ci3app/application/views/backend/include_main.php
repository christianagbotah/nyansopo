<link rel="stylesheet" href="<?=base_url('node_modules/flowbite/dist/flowbite.css')?>">
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
<link rel="shortcut icon" href="<?php echo base_url('uploads/school_logo.png');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/font-awesomenew/css/fontawesome.min.css');?>">

<!-- Tailwindcss -->
<link rel="stylesheet" href="<?php echo base_url('assets/tailwindcss/output.css');?>">

<link rel="stylesheet" href="<?php echo base_url('assets/js/vertical-timeline/css/component.css');?>">


<link rel="stylesheet" href="<?php echo base_url('assets/js/wysihtml5/bootstrap-wysihtml5.css');?>">

<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>

<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/cdn/css/dataTables.dataTables.css"/>
<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/cdn/css/buttons.dataTables.css"/>


<!--FontAwesome - Local version for offline use-->
<script src="<?php echo base_url('assets/fontawesome/6.7.2/js/all.min.js');?>" crossorigin="anonymous"></script>

<!--Amcharts-->
<!-- <script src="<?php //echo base_url('assets/js/amcharts/amcharts.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/pie.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/serial.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/gauge.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/funnel.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/radar.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/exporting/amexport.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/exporting/rgbcolor.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/exporting/canvg.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/exporting/jspdf.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/exporting/filesaver.js');?>" type="text/javascript"></script>
<script src="<?php //echo base_url('assets/js/amcharts/exporting/jspdf.plugin.addimage.js');?>" type="text/javascript"></script> -->

<!--Chartjs-->
<script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>

<!--number_format-->
<script src="<?php echo base_url('assets/js/number_format.js');?>" type="text/javascript"></script>

<style>
@media (min-width: 768px) and (orientation: landscape) {
	#main_page > div:first-child { margin-top: 10rem !important; }
}

@media (max-width: 767px) and (orientation: landscape) {
	#main_page > div:first-child { margin-top: 10rem !important; }
}
</style>


	<?php if($page_name == 'manage_attendance_view'): 
		$display = 'none';
	endif;


	    //creche
    $this->db->select('class_id');
    $this->db->distinct();
    $find_teacher_creche = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')));
    $class_ids_creche = $find_teacher_creche->result_array();

    //general
    $this->db->select('class_id');
    $this->db->distinct();
    $this->db->where('teacher_id', $this->session->userdata('teacher_id'));
    $this->db->where('year', $running_year);
    $this->db->where('term', $running_term);
    //$this->db->or_where('sem', $running_sem);
    $find_teacher = $this->db->get('subject');

    /*if($find_teacher->num_rows() == 0) {
        $this->db->select('class_id');
        $this->db->distinct();
        $this->db->where('teacher_id', $this->session->userdata('teacher_id'));
        $this->db->where('year', $running_year);
        $this->db->where('sem', $running_sem);
        //$this->db->or_where('sem', $running_sem);
        $find_teacher = $this->db->get('subject');
    }*/
    
    $class_ids = $find_teacher->result_array();

    //general for class teacher
    $this->db->select('class_id');
    $this->db->distinct();
    $find_teacher_c = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')));
    $class_ids_c = $find_teacher_c->result_array();

    //class teacher id
    if(isset($class_id)) {
        $class_result = $this->db->get_where('class', array('class_id' => $class_id));
        $class_teacher = ($class_result->num_rows() > 0) ? $class_result->row()->teacher_id : null;
    } else {
        $class_teacher = null;
    }
		?>

	<div class="">
        <!-- <h3 style="">
            <i class="entypo-right-circled"></i>
            <?php //echo $page_title;?>
        </h3> -->
        <?php 
        // Check if page is in attendance directory
        if(strpos($page_name, 'attendance/') === 0) {
            $attendance_file = str_replace('attendance/', '', $page_name);
            include 'attendance/'.$attendance_file.'.php';
        } else if(strpos($page_name, 'examination/') === 0) {
            $attendance_file = str_replace('examination/', '', $page_name);
            include 'examination/'.$attendance_file.'.php';
        } else {
            include $account_type.'/'.$page_name.'.php';
        }
        ?>
    </div>



	<script type="text/javascript">
		$(function() {

			let $page_name = '<?php echo $page_name; ?>';

			if($page_name == 'attendance_report_view' || $page_name == 'marks' || $page_name == 'marks_raw_score'  || $page_name == 'student_payment' || $page_name == 'marks_manage' || $page_name == 'marks_manage_view' || $page_name == 'marks_manage_view_creche' || $page_name == 'fct_owe_list' || $page_name == 'exam_marks_sms' || $page_name == 'students_daily_attendance' || $page_name == 'portfolio_assessment_manage_view' || $page_name == 'portfolio_assessment_manage') {
				$('body').addClass('sidebar-collapsed');
			}
		})
	</script>

		