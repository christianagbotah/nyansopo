<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-5">
<div id="pre_notice"><center><h3 style="color: #fff;"><i class="fa fa-spinner fa-pulse"></i> Loading please wait...</h3><p style="color: #b3aeae;">Getting attendance page ready</p></center>
</div>

<style type="text/css">
    #pre_notice {
      position: fixed;
      z-index: 99999;
      top: 0;
      left: 0;
      bottom: 0;
      right: 0;
      background: rgba(0, 0, 0, 0.9);
      transition: 1s 0.4s;
    }

    #pre_notice h3 {
        margin-top: 45vh;
    }
</style>

<?php
    
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    
    $_level = $this->db->get_where('admin', array('admin_id' => $this->session->userdata('admin_id')))->row()->level;

    $students_ids = explode('-', $student_id); //selected student(s)


    $class_name = $this->crud_model->get_class_name($class_id);

    ?>
<div class="border-t border-gray-200 my-6"></div>
<?php 

//total fees received today
$this->db->select_sum('feeding_paid');
$this->db->from('feeding_fee');
$this->db->where('timestamp', $timestamp);
$this->db->where('class_id', $class_id);
$feedingPaidToday = $this->db->get()->row()->feeding_paid;

$this->db->select_sum('classes_paid');
$this->db->from('feeding_fee');
$this->db->where('timestamp', $timestamp);
$this->db->where('class_id', $class_id);
$classesPaidToday = $this->db->get()->row()->classes_paid;

$timestamp_today = strtotime(date('d-m-Y'));

if($timestamp == '') {

    $selectedTimestamp = $timestamp_today;

} else {

    $selectedTimestamp = $timestamp;
}


$this->db->select('timestamp');
$this->db->from('feeding_fee');
$this->db->where('class_id', $class_id);
//$this->db->where('year', $running_year);
//$this->db->where('term', $running_term);
$this->db->where('timestamp <', $selectedTimestamp);
$this->db->order_by('timestamp', 'desc');
$this->db->limit(1);
$fc_timestamp_query1 = $this->db->get();

if($fc_timestamp_query1->num_rows() > 0) {
    $fc_timestamp_query = $fc_timestamp_query1->row();
    $previous_day_timestamp = $fc_timestamp_query->timestamp; //selecting just the previously entered timestamp for this particular class

} else {
    $previous_day_timestamp = $today_timestamp;
}


//selecting the first date recorded for feeding and classes fees
$this->db->select('timestamp');
$this->db->from('feeding_fee');
$this->db->where('class_id', $class_id);
$this->db->where('year', $running_year);
$this->db->where('term', $running_term);
$this->db->order_by('timestamp', 'asc');
$this->db->limit(1);
$fc_query1 = $this->db->get();

if($fc_query1->num_rows() > 0) {
    $fc_query = $fc_query1->row();
    $first_day_timestamp_fc = $fc_query->timestamp; //selecting just the previously entered timestamp for this particular student

} else {
    $first_day_timestamp_fc = $timestamp; //strtotime(date('d-m-Y'));
}


//general
$feeding_query = $this->db->get_where('class' , array('class_id' => $class_id) )->row();
$feeding_fee_charged    =   $feeding_query->feeding_fee;
$classes_fee_charged    =   $feeding_query->classes_fee;


echo form_open(site_url('admin/attendance_selector/'), array('id' => 'att_selector_form'));?>
<div id="filter_card" style="display: none;">
<div class="grid grid-cols-1 md:grid-cols-4 gap-5">
    <?php if($account_type == 'teacher') { ?>
        <div>
            <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('class'); ?></label>
            <select name="class_id" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" onchange="select_section(this.value); select_students(this.value)" id="class_selection">
                <option value=""><?php echo get_phrase('select_class'); ?></option>
                <?php getFullClassList($this->session->userdata('teacher_id'), $class_id); ?>
            </select>
        </div>
    <?php } else { ?>
        <div>
            <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('class'); ?></label>
            <select name="class_id" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" onchange="select_section(this.value); select_students(this.value)" id="class_selection">
                <option value=""><?php echo get_phrase('select_class'); ?></option>
                <?php getFullClassList('', $class_id); ?>
            </select>
        </div>
    <?php } ?>

    <div id="section_holder">
        <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('section'); ?></label>
        <select name="section_id" id="section_id" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5">
            <?php
            $sections = $this->db->get_where('section', array('class_id' => $class_id))->result_array();
            foreach ($sections as $row): ?>
                <option value="<?php echo $row['section_id']; ?>" <?php if ($section_id == $row['section_id']) echo 'selected'; ?>>
                    <?php echo $row['name']; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('date'); ?></label>
        <input type="text" id="time_picker" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5 datepicker" name="timestamp" data-end-date="<?= date('d-m-Y');?>" data-format="dd-mm-yyyy" value="<?php echo date("d-m-Y", $timestamp); ?>"/>
    </div>

    <div class="flex items-end">
        <button type="submit" id="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-bold rounded-lg text-base px-6 py-3.5 transition-all duration-200">
            <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <?php echo get_phrase('manage_attendance'); ?>
        </button>
    </div>

    <input type="hidden" name="year" value="<?php echo $running_year; ?>">
    <input type="hidden" name="term" value="<?php echo $running_term; ?>">
</div>
</div>
<div id="sticky_spacer" style="height: 0px;"></div>


<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div id="students_holder" style="display: none"></div>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 flex flex-col gap-6 max-h-screen overflow-y-auto">
            <div class="flex items-center gap-4 mb-6">
                <div class="bg-blue-600 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">ATTENDANCE FOR <?php echo $this->db->get_where('class', array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric; ?></h3>
                    <p class="text-base text-gray-600"><?php echo get_phrase('section'); ?> <?php echo $this->db->get_where('section', array('section_id' => $section_id))->row()->name; ?> ? <?php echo date("d M Y", $timestamp); ?></p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
                <?php if($account_type == 'teacher') { ?>
                    <div>
                        <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('class'); ?></label>
                        <select name="class_id_display" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" onchange="select_section(this.value); select_students(this.value); document.getElementById('class_selection').value = this.value;" id="class_selection_display">
                            <option value=""><?php echo get_phrase('select_class'); ?></option>
                            <?php getFullClassList($this->session->userdata('teacher_id'), $class_id); ?>
                        </select>
                    </div>
                <?php } else { ?>
                    <div>
                        <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('class'); ?></label>
                        <select name="class_id_display" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" onchange="select_section(this.value); select_students(this.value); document.getElementById('class_selection').value = this.value;" id="class_selection_display">
                            <option value=""><?php echo get_phrase('select_class'); ?></option>
                            <?php getFullClassList('', $class_id); ?>
                        </select>
                    </div>
                <?php } ?>
                <div id="section_holder_display">
                    <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('section'); ?></label>
                    <select name="section_id_display" id="section_id_display" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" onchange="document.getElementById('section_id').value = this.value;">
                        <?php
                        $sections = $this->db->get_where('section', array('class_id' => $class_id))->result_array();
                        foreach ($sections as $row): ?>
                            <option value="<?php echo $row['section_id']; ?>" <?php if ($section_id == $row['section_id']) echo 'selected'; ?>>
                                <?php echo $row['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('date'); ?></label>
                    <input type="text" id="time_picker_display" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5 datepicker" name="timestamp_display" data-end-date="<?= date('d-m-Y');?>" data-format="dd-mm-yyyy" value="<?php echo date("d-m-Y", $timestamp); ?>" onchange="document.getElementById('time_picker').value = this.value;"/>
                </div>
                <div class="flex items-end">
                    <button type="submit" id="submit_display" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-bold rounded-lg text-base px-6 py-3.5 transition-all duration-200">
                        <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <?php echo get_phrase('manage'); ?>
                    </button>
                </div>
            </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" onclick="autoBillStudents()" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Auto Bill Students
                    </button>
                    <button type="button" onclick="bulkPayment()" class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Bulk Payment
                    </button>
                    <button type="button" onclick="launchTemplateView()" class="bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Attendance
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-4 mt-10">
                    <div class="bg-green-50 rounded-lg p-4 border-2 border-green-200">
                        <p class="text-sm font-semibold text-green-800 mb-1">FEEDING FEE TOTAL</p>
                        <p class="text-2xl font-bold text-green-600"><?=numfmt_format_currency($fmt, $feedingPaidToday, $currency); ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
                        <p class="text-sm font-semibold text-blue-800 mb-1">CLASSES FEE TOTAL</p>
                        <p class="text-2xl font-bold text-blue-600"><?=numfmt_format_currency($fmt, $classesPaidToday, $currency); ?></p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 border-2 border-purple-200">
                        <p class="text-sm font-semibold text-purple-800 mb-1">TRANSPORT FEE TOTAL</p>
                        <p class="text-2xl font-bold text-purple-600">GHS 0.00</p>
                    </div>
                </div>
            </div>            
        </div>
    </div>
</div>
    <?php echo form_close(); ?>
<div class="border-t border-gray-200 my-6"></div>



<div class="w-full mt-4 px-4 md:px-10">

        <div class="flex" id="wrong_date_alert" style="display: none;">
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-4">
                <p class="text-red-700 font-semibold">Selected Date is below the first date you collected fees in this term. System cannot allow you to do any changes to fees on this date.</p>
            </div>
        </div>

        <div class="flex justify-end mb-4">
            <button type="button" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-6 rounded-lg transition-colors" onclick="launchTemplateView()">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Attendance List
            </button>
        </div>


        <?php echo form_open(site_url('admin/attendance_update/'. $class_id . '/' . $section_id . '/' . $timestamp), array('id' => 'attendance_form')); ?>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
            <div class="max-w-md mx-auto md:mx-0">
                <input type="text" id="attendance_search" placeholder="Search by name or student code..." class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" onkeyup="filterAttendanceCards(this.value)">
            </div>
        </div>
        <div id="attendance_update" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php
                    $count = 1;
                    $select_id = 0;
                    $att_id_array = array();

                    $this->db->where_in('attendance.student_id', $students_ids);
                    $this->db->join('student', 'student.student_id = attendance.student_id');
                    $this->db->order_by('name', 'asc');
                    $attendance_of_students = $this->db->get_where('attendance', array(
                                'class_id' => $class_id,
                                'section_id' => $section_id,
                                'year' => $running_year,
                                'term' => $running_term,
                                'timestamp' => $timestamp
                            ));

                    $attendance_of_students_array = $attendance_of_students->result_array();
                    if($attendance_of_students->num_rows() < 1) {
                        if($running_term == 1) {
                            $this->session->set_flashdata('error_message', get_phrase('be_sure_you_have_promoted_students_during_term_3._please_contact_the_administrator_for_assistance'));

                            echo '<div class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
                                    <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                    </svg>
                                    <div><span class="font-semibold">'.get_phrase('be_sure_you_have_promoted_students_during_term_3._if_you_have_not_done_it,_please_contact_the_administrator_for_assistance').'</span></div>
                                </div>';
                        }else{
                            $this->session->set_flashdata('error_message', get_phrase('no_record_was_found_for_this_class'));

                            echo '<div class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
                                    <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                    </svg>
                                    <div><span class="font-semibold">'.get_phrase('no_record_was_found_for_this_class.').' Possible Reason: Maybe You Have Not Admitted Any Student In This Class Yet.</span></div>
                                </div>';
                        }
                    }
                    
            if($attendance_of_students->num_rows() > 0) {

                    foreach ($attendance_of_students_array as $row):

                        $special_diet = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->special_diet;
                       /* if($special_diet == 1) {
                            $feeding_fee_charged = 0;
                            $feeding_fee_charged_b = 0;
                        }*/


                        //for selecting feeding fee and classes fee payers
                        $feeding_paid_rows = $this->db->get_where('feeding_fee' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$timestamp));

                        if($feeding_paid_rows->num_rows() > 0) {
                            $feeding_paid = $feeding_paid_rows->row()->feeding_paid;
                            $classes_paid = $feeding_paid_rows->row()->classes_paid;
                        } else {
                            $feeding_paid = 0;
                            $classes_paid = 0;
                        }

                        //feeding and classes fee owe as at the previous day
                        $feeding_fee_owe = $this->db->get_where('feeding_fee' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$previous_day_timestamp))->row()->due;

                        $classes_fee_owe = $this->db->get_where('feeding_fee' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$previous_day_timestamp))->row()->cdue;

                        $feeding_owe_now = $this->db->get_where('feeding_fee' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$timestamp));

                        // Get benefit category details
                        $feeding_discount = 0;
                        $classes_discount = 0;
                        $hide_feeding = false;
                        $hide_classes = false;
                        
                        // Check if student is beneficiary using beneficiary_list table
                        $is_beneficiary = false;
                        if($this->db->table_exists('beneficiary_list')) {
                            $benefit_check = $this->db->get_where('beneficiary_list', array('student_id' => $row['student_id'], 'year' => $running_year, 'term' => $running_term));
                            $is_beneficiary = ($benefit_check->num_rows() > 0);
                        } else {
                            // Fallback to old system
                            $student_data = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
                            $is_beneficiary = ($student_data && $student_data->benefit_status == 1);
                        }
                        
                        if($is_beneficiary) {
                            $categories = array();
                            
                            // Try new system first
                            if($this->db->table_exists('beneficiary_list')) {
                                $benefit_list = $this->db->get_where('beneficiary_list', array('student_id' => $row['student_id'], 'year' => $running_year, 'term' => $running_term))->row();
                                if($benefit_list && !empty($benefit_list->categories)) {
                                    $categories = json_decode($benefit_list->categories, true);
                                }
                            }
                            
                            // Fallback to old system
                            if(empty($categories)) {
                                $student_data = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
                                if($student_data && !empty($student_data->benefit_category_id)) {
                                    $categories = array(array('category_id' => $student_data->benefit_category_id));
                                }
                            }
                            
                            // Process categories
                            foreach($categories as $cat) {
                                $category_id = $cat['category_id'];
                                $benefit_cat = $this->db->get_where('benefit_category', array('category_id' => $category_id))->row();
                                if($benefit_cat) {
                                    $details = json_decode($benefit_cat->details, true);
                                    $discount_type = isset($details['discount_type']) ? $details['discount_type'] : 'percentage';
                                    // Check for specific class first, then fallback to 'a' (All Classes)
                                    $class_details = isset($details['classes'][$class_id]) ? $details['classes'][$class_id] : (isset($details['classes']['a']) ? $details['classes']['a'] : null);
                                    
                                    if($class_details) {
                                        if(isset($class_details['feeding_charged'])) {
                                            if($discount_type == 'percentage') {
                                                $feeding_discount += ($feeding_fee_charged * $class_details['feeding_charged']) / 100;
                                                if($class_details['feeding_charged'] >= 100) $hide_feeding = true;
                                            } else {
                                                $feeding_discount += $class_details['feeding_charged'];
                                                if($feeding_discount >= $feeding_fee_charged) $hide_feeding = true;
                                            }
                                        }
                                        
                                        if(isset($class_details['classes_charged'])) {
                                            if($discount_type == 'percentage') {
                                                $classes_discount += ($classes_fee_charged * $class_details['classes_charged']) / 100;
                                                if($class_details['classes_charged'] >= 100) $hide_classes = true;
                                            } else {
                                                $classes_discount += $class_details['classes_charged'];
                                                if($classes_discount >= $classes_fee_charged) $hide_classes = true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        $feeding_fee_charged_b = $feeding_fee_charged - $feeding_discount;
                        $classes_fee_charged_b = $classes_fee_charged - $classes_discount;

                        if($feeding_owe_now->num_rows() > 0) {

                            //for feeding fee
                            if($feeding_owe_now->row()->due == 0) {
                                $feeding_owe = '0';
                            } else if($feeding_owe_now->row()->due > 0 || $feeding_owe_now->row()->due < 0) {

                            $feeding_owe_now_after_payment = $feeding_owe_now->row()->due;
                                $feeding_owe = $feeding_owe_now_after_payment;
                            }   

                            //for classes fee
                            if($feeding_owe_now->row()->cdue == 0) {
                                $classes_owe = '0';
                            } else if($feeding_owe_now->row()->cdue > 0 || $feeding_owe_now->row()->cdue < 0) {

                                $classes_owe_now_after_payment = $feeding_owe_now->row()->cdue;
                                $classes_owe = $classes_owe_now_after_payment;
                            }                        
                        } else {
                            //checking if the student is a beneficiary or not
                            if($benefit_status == 0) {
                                $feeding_owe = $feeding_fee_owe + $feeding_fee_charged;
                                $classes_owe = $classes_fee_owe + $classes_fee_charged;
                            } else {
                                $feeding_owe = $feeding_fee_owe + $feeding_fee_charged_b;
                                $classes_owe = $classes_fee_owe + $classes_fee_charged_b;
                            }
                        }

                        //for transportation
                        //for selecting transport fare payers
                        $transport_paid_rows = $this->db->get_where('transport_fare' , array('student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'payment_date'=>date('Y-m-d', $timestamp)));

                        if($transport_paid_rows->num_rows() > 0) {
                            $fare_paid = $transport_paid_rows->row()->amount_paid;
                        } else {
                            $fare_paid = 0;
                        }

                        //transport fare owe as at the previous day
                        $transport_fare_owe = $this->db->get_where('transport_fare' , array('student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'payment_date'=>date('Y-m-d', $previous_day_timestamp)))->row()->due;

                        //transport id from enroll table
                        $enroll_query = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'year' => $running_year, 'class_id' => $class_id));
                        $transport_id = ($enroll_query->num_rows() > 0) ? $enroll_query->row()->transport_id : null;

                        //transport fare from transport table
                        if(!empty($transport_id) && $transport_id != 0) {
                            $transport_query = $this->db->get_where('transport', array('transport_id' => $transport_id));
                            $transport_fare_charged = ($transport_query->num_rows() > 0) ? $transport_query->row()->route_fare : 0;
                        } else {
                            $transport_fare_charged = 0;
                        }

                        $fare_owe_now = $this->db->get_where('transport_fare' , array('student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'payment_date'=>date('Y-m-d', $timestamp)));

                        if($fare_owe_now->num_rows() > 0) {

                            if($fare_owe_now->row()->due == 0) {
                                $fare_owe = '0';
                            } else if($fare_owe_now->row()->due > 0 || $fare_owe_now->row()->due < 0) {

                            $fare_owe_now_after_payment = $fare_owe_now->row()->due;
                                $fare_owe = $fare_owe_now_after_payment;
                            }                           
                        } else {
                                $fare_owe = $transport_fare_owe + $transport_fare_charged;
                        }



                        ?>

                        <?php 
                        $cardColors = [
                            'bg-blue-50 border-blue-200',
                            'bg-green-50 border-green-200',
                            'bg-purple-50 border-purple-200',
                            'bg-orange-50 border-orange-200',
                            'bg-pink-50 border-pink-200',
                            'bg-cyan-50 border-cyan-200',
                            'bg-yellow-50 border-yellow-200',
                            'bg-indigo-50 border-indigo-200'
                        ];
                        $colorIndex = ($count - 1) % count($cardColors);
                        $cardColor = $cardColors[$colorIndex];
                        ?>
                        <div class="<?php echo $cardColor; ?> rounded-xl shadow-md border-2 p-6 hover:shadow-lg transition-shadow attendance-card" data-student-name="<?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?>" data-student-code="<?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->student_code; ?>">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-100 text-blue-800 font-bold rounded-full w-10 h-10 flex items-center justify-center">
                                        <?php echo $count++; ?>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900"><?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?></h3>
                                        <p class="text-base font-semibold text-gray-700"><?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->student_code; ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="getStudentBillingInfo(<?php echo $row['student_id']; ?>)" class="bg-gray-500 hover:bg-gray-600 text-white text-xs px-2 py-1 rounded transition-colors" title="View Billing Info">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-800 mb-2">Attendance Status</label>
                                <select class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 font-semibold w-full" onchange="is_student_present('<?= $row['student_id']; ?>', '<?=$special_diet; ?>')" name="status_<?php echo $row['attendance_id']; ?>" id="status_<?php echo $row['student_id']; ?>">
                                    <option value="1" <?php if ($row['status'] == 1) echo 'selected'; ?>><?php echo get_phrase('present_(P)'); ?></option>
                                    <option value="2" <?php if ($row['status'] == 2) echo 'selected'; ?>><?php echo get_phrase('absent_(A)'); ?></option>
                                    <option value="3" <?php if ($row['status'] == 3) echo 'selected'; ?>><?php echo get_phrase('busy_(B)'); ?></option>
                                    <option value="4" <?php if ($row['status'] == 4) echo 'selected'; ?>><?php echo get_phrase('sick-Home_(S)'); ?></option>
                                    <option value="5" <?php if ($row['status'] == 5) echo 'selected'; ?>><?php echo get_phrase('sick-Clinic_(C)'); ?></option>
                                </select>
                            </div>
                            <?php $special_diet = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->special_diet; ?>
                            <div class="space-y-3">
                                <?php if(!$hide_feeding): ?>
                                <div class="bg-green-50 rounded-lg p-3 border-2 border-green-200">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-bold text-green-800 mb-2"><?php echo get_phrase('feeding_fee_paid'); ?></label>
                                            <input type="number" onkeyup="student_paid_feeding('<?= $row['student_id']; ?>'); updateOweField('<?= $row['student_id']; ?>', 'feeding');" name="feeding_paid_<?php echo $row['student_id']; ?>" class="fee-input bg-white border-2 border-green-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-2.5" value="<?= $special_diet == 1 ? 0 : $feeding_paid; ?>" id="feeding_paid_<?php echo $row['student_id']; ?>" <?php if($special_diet == 1) echo 'disabled'; ?>>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-green-800 mb-2"><?php echo get_phrase('amount_owe'); ?></label>
                                            <input type="number" onchange="change_color()" value="<?= $special_diet == 1 ? 0 : $feeding_owe; ?>" name="feeding_owe_<?php echo $row['student_id']; ?>" class="fee-input border-2 text-base rounded-lg block w-full p-2.5 font-bold" style="<?php if($feeding_owe > 0) echo 'background-color: #fee2e2; color: #991b1b; border-color: #ef4444;'; elseif($feeding_owe == 0) echo 'background-color: #d1fae5; color: #065f46; border-color: #10b981;'; else echo 'background-color: #dbeafe; color: #1e40af; border-color: #3b82f6;'; ?>" readonly id="feeding_owe_<?php echo $row['student_id']; ?>" <?php if($special_diet == 1) echo 'disabled'; ?>>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if(!$hide_classes): ?>
                                <div class="bg-blue-50 rounded-lg p-3 border-2 border-blue-200">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-bold text-blue-800 mb-2"><?php echo get_phrase('classes_fee_paid'); ?></label>
                                            <input type="number" onkeyup="student_paid_classes('<?= $row['student_id']; ?>'); updateOweField('<?= $row['student_id']; ?>', 'classes');" name="classes_paid_<?php echo $row['student_id']; ?>" class="fee-input bg-white border-2 border-blue-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" value="<?= $classes_paid; ?>" id="classes_paid_<?php echo $row['student_id']; ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-blue-800 mb-2"><?php echo get_phrase('amount_owe'); ?></label>
                                            <input type="number" onchange="change_color()" value="<?= $classes_owe; ?>" name="classes_owe_<?php echo $row['student_id']; ?>" class="fee-input border-2 text-base rounded-lg block w-full p-2.5 font-bold" style="<?php if($classes_owe > 0) echo 'background-color: #fee2e2; color: #991b1b; border-color: #ef4444;'; elseif($classes_owe == 0) echo 'background-color: #d1fae5; color: #065f46; border-color: #10b981;'; else echo 'background-color: #dbeafe; color: #1e40af; border-color: #3b82f6;'; ?>" readonly id="classes_owe_<?php echo $row['student_id']; ?>">
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($transport_id) && $transport_id != 0): ?>
                                <div class="bg-purple-50 rounded-lg p-3 border-2 border-purple-200">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-bold text-purple-800 mb-2"><?php echo get_phrase('transport_fare_paid'); ?></label>
                                            <input type="number" onkeyup="student_paid_transport('<?= $row['student_id']; ?>')" name="transport_paid_<?php echo $row['student_id']; ?>" class="fee-input bg-white border-2 border-purple-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full p-2.5" value="<?= $fare_paid; ?>" id="transport_paid_<?php echo $row['student_id']; ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-purple-800 mb-2"><?php echo get_phrase('amount_owe'); ?></label>
                                            <input type="number" onchange="change_color()" value="<?= $fare_owe; ?>" data-original-owe="<?= $fare_owe; ?>" name="transport_owe_<?php echo $row['student_id']; ?>" class="fee-input border-2 text-base rounded-lg block w-full p-2.5 font-bold" style="<?php if($fare_owe > 0) echo 'background-color: #fee2e2; color: #991b1b; border-color: #ef4444;'; elseif($fare_owe == 0) echo 'background-color: #d1fae5; color: #065f46; border-color: #10b981;'; else echo 'background-color: #dbeafe; color: #1e40af; border-color: #3b82f6;'; ?>" readonly id="transport_owe_<?php echo $row['student_id']; ?>">
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php
                    $st_id_array[$select_id] = $row['student_id'];
                    $select_id++;
                    endforeach; 

                }
                    ?>
        </div>

        <input type="hidden" name="payment_time" value="<?php echo date('H:i:s');?>">
        <input type="hidden" name="students_ids" value="<?php echo $student_id;?>">
        <div class="fixed bottom-6 right-6 z-50">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-lg text-lg transition-all shadow-lg hover:shadow-xl" id="submit_button">
                <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save All
            </button>
        </div>

        <div id="notifier" class="bg-gray-900 text-white font-bold p-4 rounded-lg mt-4 text-center" style="display: none">
            <p class="text-lg">Processing Data. Please Wait <i class="fa fa-spinner fa-pulse"></i></p>
            <p class="text-sm mt-2"><em>If this takes too much time, then please check your network connection and try again.</em></p>
        </div>

        <div id="empty_error" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mt-4" style="display: none;">
            <p class="text-red-700 font-semibold">Some Students' Attendance Status Is Unknown. Please Make Sure You Set The "Show Entries" Box At The Top Left Corner Of The Form To A Number More Than The Total Number Of Students In The Class, Then Mark Attendance Again. E.g. You Can Set It To 25, 50, 100 etc. <i>Note: The Default Entry is 10.</i></p>
        </div>
        <?php echo form_close(); ?>

    </div>

</div>
    

<script type="text/javascript">
    
$(function() {

    //disable submit button on page load
    $('#submit_button').attr('disabled', 'disabled');

    $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        }); 

    select_students(<?=$class_id ?>); //show class members
    is_student_present_onload();
    
    // Attendance search filter
    setTimeout(function() {
        const searchInput = document.getElementById('attendance_search');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.attendance-card');
                cards.forEach(function(card) {
                    const studentName = (card.getAttribute('data-student-name') || '').toLowerCase();
                    const studentCode = (card.getAttribute('data-student-code') || '').toLowerCase();
                    if(studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    }, 500);
    
    // Adjust grid columns based on sidebar state
    function adjustGridColumns() {
        const attendanceGrid = document.getElementById('attendance_update');
        if(!attendanceGrid) return;
        
        const isSidebarCollapsed = document.body.classList.contains('sidebar-collapse') || 
                                   document.body.classList.contains('sidebar-mini') ||
                                   document.querySelector('.main-sidebar')?.classList.contains('sidebar-collapse');
        
        if(isSidebarCollapsed) {
            attendanceGrid.classList.remove('md:grid-cols-3', 'lg:grid-cols-4');
            attendanceGrid.classList.add('md:grid-cols-4', 'lg:grid-cols-5');
        } else {
            attendanceGrid.classList.remove('md:grid-cols-4', 'lg:grid-cols-5');
            attendanceGrid.classList.add('md:grid-cols-3', 'lg:grid-cols-4');
        }
    }
    
    setTimeout(adjustGridColumns, 100);
    
    // Watch for sidebar toggle
    const observer = new MutationObserver(adjustGridColumns);
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    
    // Also watch sidebar element if it exists
    const sidebar = document.querySelector('.main-sidebar');
    if(sidebar) {
        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    }
    
    // Listen for sidebar toggle button clicks
    $(document).on('click', '[data-widget="pushmenu"], .sidebar-toggle', function() {
        setTimeout(adjustGridColumns, 300);
    });
    
    // Sticky filter card
    let filterCard = document.getElementById('filter_card');
    let filterCardOffset = 0;
    let isSticky = false;
    const mainHeaderHeight = 60;
    
    window.addEventListener('scroll', function() {
        if(!filterCard) return;
        
        if(filterCardOffset === 0) {
            filterCardOffset = filterCard.offsetTop;
        }
        
        if(window.pageYOffset > filterCardOffset - mainHeaderHeight) {
            if(!isSticky) {
                filterCard.style.position = 'fixed';
                filterCard.style.top = mainHeaderHeight + 'px';
                filterCard.style.left = '50%';
                filterCard.style.transform = 'translateX(-50%)';
                filterCard.style.width = 'calc(100% - 3rem)';
                filterCard.style.maxWidth = '80rem';
                filterCard.style.zIndex = '100';
                document.getElementById('sticky_spacer').style.height = filterCard.offsetHeight + 'px';
                isSticky = true;
            }
        } else {
            if(isSticky) {
                filterCard.style.position = 'relative';
                filterCard.style.top = 'auto';
                filterCard.style.left = 'auto';
                filterCard.style.transform = 'none';
                filterCard.style.width = 'auto';
                document.getElementById('sticky_spacer').style.height = '0px';
                isSticky = false;
            }
        }
    });

    // Auto bill students when attendance is marked as present
    function autoBillOnPresent(studentId) {
        var att_status = $('#status_' + studentId).val();
        if(att_status == '1') { // Present
            // Auto bill this student
            $.ajax({
                url: '<?php echo site_url('admin/auto_bill_students'); ?>',
                type: 'POST',
                data: {
                    class_id: <?php echo $class_id; ?>,
                    section_id: <?php echo $section_id; ?>,
                    timestamp: <?php echo $timestamp; ?>,
                    year: '<?php echo $running_year; ?>',
                    term: '<?php echo $running_term; ?>',
                    single_student: studentId
                },
                success: function(response) {
                    // Update the billing display for this student
                    updateStudentBillingDisplay(studentId);
                }
            });
        }
    }

    // Update student billing display
    function updateStudentBillingDisplay(studentId) {
        $.ajax({
            url: '<?php echo site_url('admin/get_student_billing_info'); ?>',
            type: 'POST',
            data: {
                student_id: studentId,
                timestamp: <?php echo $timestamp; ?>,
                class_id: <?php echo $class_id; ?>
            },
            success: function(response) {
                var data = JSON.parse(response);
                if(data.status == 'success') {
                    $('#feeding_owe_' + studentId).val(data.feeding_due);
                    $('#classes_owe_' + studentId).val(data.classes_due);
                    // Update color coding
                    updateOweColors(studentId);
                }
            }
        });
    }

    // Update owe amount colors
    function updateOweColors(studentId) {
        var feedingOwe = parseFloat($('#feeding_owe_' + studentId).val()) || 0;
        var classesOwe = parseFloat($('#classes_owe_' + studentId).val()) || 0;

        // Feeding owe color
        if(feedingOwe > 0) {
            $('#feeding_owe_' + studentId).css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
        } else if(feedingOwe == 0) {
            $('#feeding_owe_' + studentId).css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
        } else {
            $('#feeding_owe_' + studentId).css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
        }

        // Classes owe color
        if(classesOwe > 0) {
            $('#classes_owe_' + studentId).css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
        } else if(classesOwe == 0) {
            $('#classes_owe_' + studentId).css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
        } else {
            $('#classes_owe_' + studentId).css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
        }
    }

    // Enhanced attendance status change with auto-billing
    function enhancedAttendanceChange(studentId, specialDiet) {
        is_student_present(studentId, specialDiet);

        // Auto bill if marked present
        setTimeout(function() {
            autoBillOnPresent(studentId);
        }, 500);
    }

    //feeding fee owe calculator
    // feeding_owe_calculator();

        //$('#export_table').dataTable();

//remove the pre notifier after page has fully loaded

    $(window).on("load", function() {
        //disable submit button on page load
        $('#submit_button').attr('disabled', 'disabled');

        $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        });
        
        // Reinitialize attendance search
        const searchInput = document.getElementById('attendance_search');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.attendance-card');
                cards.forEach(function(card) {
                    const studentName = (card.getAttribute('data-student-name') || '').toLowerCase();
                    const studentCode = (card.getAttribute('data-student-code') || '').toLowerCase();
                    if(studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
        
        // Adjust grid on load
        setTimeout(function() {
            const attendanceGrid = document.getElementById('attendance_update');
            if(!attendanceGrid) return;
            
            const isSidebarCollapsed = document.body.classList.contains('sidebar-collapse') || 
                                       document.body.classList.contains('sidebar-mini') ||
                                       document.querySelector('.main-sidebar')?.classList.contains('sidebar-collapse');
            
            if(isSidebarCollapsed) {
                attendanceGrid.classList.remove('md:grid-cols-3', 'lg:grid-cols-4');
                attendanceGrid.classList.add('md:grid-cols-4', 'lg:grid-cols-5');
            }
        }, 100);
    
    });

    
});

function launchTemplateView() {

    //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

    showAjaxModalDisplayLarge('<?php echo site_url('modal/popup/template_attendance_fees_collection') ?>');
}

    //for firefox browser
    window.onload = function() {
        //disable submit button on page load
        $('#submit_button').attr('disabled', 'disabled');

        $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        }); 
    };

//check if there's internet connectivity
/*const checkOnlineStatus = async () => {
    try {
        const response = await fetch('https://jsonplaceholder.typicode.com/posts?id=1', 
        );
        return response.status >= 200 && response.status < 300;

    } catch(err) {
        return false;
    }
}*/


//before submitting the attendance data, for these:
//1. if a student was not checked-in, yet marked present by teacher, abort update
//2. if a student was checked in but marked absent by teacher here, abort update

function verify_check_in() {

    //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

      showAjaxModal_alert('Please wait, we are working on it <i class="fa fa-spinner fa-pulse"></i>', 'Loading');

    let form_data = $('#attendance_form').serialize();

    $.ajax({
        url: '<?php echo site_url('admin/verify_check_in/'.$class_id .'/'. $timestamp) ?>',
        type: 'POST',
        dataType: 'html',
        data: form_data,
    })
    .done(function(response) {

        if(response.length > 0) {
            showAjaxModal_alert(response, 'Warning');
            return false;
        } else {
            //go ahead
            $.ajax({
                url: '<?php echo site_url('admin/attendance_update/'.$class_id .'/'. $section_id.'/'. $timestamp) ?>',
                type: 'POST',
                dataType: 'json',
                data: form_data,
            })
            .done(function(data) {
                showAjaxModal_alert(data.message, 'Success');
                
                setTimeout(() => {
                    $('#att_selector_form').submit();
                    $('.close').click();
                    
                    //window.location.reload();
                }, 3000);
            })
            .fail(function(err) {
                showAjaxModal_alert(err.responseText, 'Error');
            });
        }
    })
    .fail(function() {
        alert("error");
    });
    
}



//to check if a student is marked absent or undefined, if so, disable that student's payment fields
function is_student_present(id, special_diet, counter) {

    //disable submit button on page load and show notifier
    $('#submit_button').attr('disabled', 'disabled');
    $('#notifier').slideDown('slow');

    var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;
    var timestamp = <?php echo $timestamp; ?>;
    let first_day_timestamp = Number(<?php echo $first_day_timestamp_fc; ?>);
    let timestamp2 = Number(<?php echo $timestamp; ?>);

    let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
    let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
    let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);
    let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

    if(special_diet == 1) {

        feeding_fee_charged = 0;
        feeding_fee_charged_b = 0;
    }

    //check if the current timestamp already exists in the feeding table
    let feeding_owe_now_rows = <?php echo $feeding_owe_now->num_rows(); ?>;
    //let fare_owe_now_rows = <?php //echo $fare_owe_now->num_rows(); ?>;

    //feeding
    let feeding_paid = Number($('#feeding_paid_' + id).val());
    let feeding_owe =  Number($('#feeding_owe_' + id).val());

    //classes
    let classes_paid = Number($('#classes_paid_' + id).val());
    let classes_owe =  Number($('#classes_owe_' + id).val());


    /*//transport
    let fare_paid = Number($('#transport_paid_' + id).val())
    let transport_fare_charged = Number($('#transport_fare_charged_' + id).val());
    let fare_owe =  Number($('#transport_owe_' + id).val());*/


    let att_status = $('#status_' + id).val();

    var class_id = Number(<?php echo $class_id; ?>);
    var previous_day_timestamp = Number(<?php echo $previous_day_timestamp; ?>);
    var feeding_paid_now = Number($('#feeding_paid_' + id).val());
    //var fare_paid_now = Number($('#transport_paid_' + id).val());

    if(first_day_timestamp > timestamp2) { //disable all input fields if user selects a date less than the 1st date
            $('#wrong_date_alert').css('display', 'block');
            $('#submit_button').attr('disabled', 'disabled');

            $('#submit_button').click(function(event) {
                return false;
            });

            $('#feeding_paid_' + id).attr('readonly', 'true');
            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
            $('#feeding_paid_' + id).val(''); 
            $('#feeding_owe_' + id).attr('readonly', 'true');
            
             
            $('#feeding_owe_' + id).removeAttr('style');

            $('#classes_paid_' + id).attr('readonly', 'true');
            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
            $('#classes_paid_' + id).val(''); 
            $('#classes_owe_' + id).attr('readonly', 'true');
            
            
            $('#classes_owe_' + id).removeAttr('style');

            /*$('#transport_paid_' + id).attr('readonly', 'true');
            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
            $('#transport_paid_' + id).val('');
            $('#transport_owe_' + id).attr('readonly', 'true');
            
            
            $('#transport_owe_' + id).removeAttr('style');*/

        } else {
            $('#wrong_date_alert').css('display', 'none');
        


            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + id,

                success: function(bf_response) {
                    if(bf_response == 'no') {
                        //feeding ajax     

                            $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status != '1') {

                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            $('#feeding_owe_' + id).val(Number(response) - feeding_fee_charged);

                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val('');
                                            $('#classes_owe_' + id).val(Number(response) - classes_fee_charged);

                                            $('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');

                                            $('#submit_button').removeAttr('disabled');

                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged + '/' + 'no',

                                                success: function(fee_paid) {

                                                    $('#feeding_paid_' + id).val(fee_paid);
                                                    $('#feeding_paid_' + id).removeAttr('readonly');
                                                    $('#feeding_paid_' + id).removeAttr('placeholder');
                                                    $('#feeding_owe_' + id).removeAttr('placeholder');
                                                    
                                                    if(fee_paid == 0 && response == 0) {
                                                        $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged);
                                                    } else {
                                                        $('#feeding_owe_' + id).val(Number(response));
                                                    }

                                                    $('#transport_paid_' + id).removeAttr('readonly');
                                                    $('#transport_paid_' + id).removeAttr('placeholder');
                                                    $('#transport_owe_' + id).removeAttr('placeholder');

                                                    $('#submit_button').removeAttr('disabled');

                                                }
                                            });

                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status != '1') {
                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            
                                            

                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val('');
                                            
                                            

                                            $('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');
                                            
                                            

                                            $('#submit_button').removeAttr('disabled');
                                        } else if(att_status == '1') {
                                            $('#feeding_paid_' + id).val(feeding_paid);
                                            $('#feeding_paid_' + id).removeAttr('readonly');
                                            $('#feeding_paid_' + id).removeAttr('placeholder');
                                            $('#feeding_owe_' + id).removeAttr('placeholder');
                                            $('#feeding_owe_' + id).val(Number(response));

                                            $('#transport_paid_' + id).removeAttr('readonly');
                                            $('#transport_paid_' + id).removeAttr('placeholder');
                                            $('#transport_owe_' + id).removeAttr('placeholder');

                                            $('#submit_button').removeAttr('disabled');
                                        }  

                                    }                     
                                }
                            });
                    
                    //classes ajax     

                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status != '1') {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            
                                            

                                            $('#submit_button').removeAttr('disabled');
                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged + '/' + 'no',

                                                success: function(class_fee_paid) {

                                                    $('#classes_paid_' + id).val(class_fee_paid);
                                                    $('#classes_paid_' + id).removeAttr('readonly');
                                                    $('#classes_paid_' + id).removeAttr('placeholder');
                                                    $('#classes_owe_' + id).removeAttr('placeholder');
                                                    
                                                    if(class_fee_paid == 0 && response == 0) {
                                                        $('#classes_owe_' + id).val(Number(response) + classes_fee_charged);
                                                    } else {
                                                        $('#classes_owe_' + id).val(Number(response));
                                                    }

                                                    $('#submit_button').removeAttr('disabled');

                                                }
                                            });
                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status != '1') {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            
                                            

                                            $('#submit_button').removeAttr('disabled');
                                        } else if(att_status == '1') {
                                            $('#classes_paid_' + id).val(feeding_paid);
                                            $('#classes_paid_' + id).removeAttr('readonly');
                                            $('#classes_paid_' + id).removeAttr('placeholder');
                                            $('#classes_owe_' + id).removeAttr('placeholder');
                                            $('#classes_owe_' + id).val(Number(response));

                                            $('#submit_button').removeAttr('disabled');
                                        }  

                                    }                     
                                }
                            });
                    } else {
                        $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status != '1') {
                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            
                                            

                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val('');
                                            
                                            

                                            $('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');
                                            
                                            

                                            $('#submit_button').removeAttr('disabled');
                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b + '/' + 'no',

                                                success: function(fee_paid) {

                                                    $('#feeding_paid_' + id).val(fee_paid);
                                                    $('#feeding_paid_' + id).removeAttr('readonly');
                                                    $('#feeding_paid_' + id).removeAttr('placeholder');
                                                    $('#feeding_owe_' + id).removeAttr('placeholder');
                                                    
                                                    if(fee_paid == 0 && response == 0) {
                                                        $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged_b);
                                                    } else {
                                                        $('#feeding_owe_' + id).val(Number(response));
                                                    }

                                                    $('#transport_paid_' + id).removeAttr('readonly');
                                                    $('#transport_paid_' + id).removeAttr('placeholder');
                                                    $('#transport_owe_' + id).removeAttr('placeholder');

                                                    $('#submit_button').removeAttr('disabled');

                                                }
                                            });

                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status != '1') {
                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            
                                            

                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val('');
                                            
                                            

                                            $('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');
                                            
                                            

                                            $('#submit_button').removeAttr('disabled');
                                        } else if(att_status == '1') {
                                            $('#feeding_paid_' + id).val(feeding_paid);
                                            $('#feeding_paid_' + id).removeAttr('readonly');
                                            $('#feeding_paid_' + id).removeAttr('placeholder');
                                            $('#feeding_owe_' + id).removeAttr('placeholder');
                                            $('#feeding_owe_' + id).val(Number(response));

                                            $('#transport_paid_' + id).removeAttr('readonly');
                                            $('#transport_paid_' + id).removeAttr('placeholder');
                                            $('#transport_owe_' + id).removeAttr('placeholder');

                                            $('#submit_button').removeAttr('disabled');
                                        }  

                                    }                     
                                }
                            });
                    
                    //classes ajax     

                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status != '1') {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            
                                            

                                            $('#submit_button').removeAttr('disabled');
                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b + '/' + 'no',

                                                success: function(class_fee_paid) {

                                                    $('#classes_paid_' + id).val(class_fee_paid);
                                                    $('#classes_paid_' + id).removeAttr('readonly');
                                                    $('#classes_paid_' + id).removeAttr('placeholder');
                                                    $('#classes_owe_' + id).removeAttr('placeholder');
                                                    
                                                    if(class_fee_paid == 0 && response == 0) {
                                                        $('#classes_owe_' + id).val(Number(response) + classes_fee_charged_b);
                                                    } else {
                                                        $('#classes_owe_' + id).val(Number(response));
                                                    }

                                                    $('#submit_button').removeAttr('disabled');

                                                }
                                            });
                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status != '1') {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            
                                            

                                            $('#submit_button').removeAttr('disabled');
                                        } else if(att_status == '1') {
                                            $('#classes_paid_' + id).val(feeding_paid);
                                            $('#classes_paid_' + id).removeAttr('readonly');
                                            $('#classes_paid_' + id).removeAttr('placeholder');
                                            $('#classes_owe_' + id).removeAttr('placeholder');
                                            $('#classes_owe_' + id).val(Number(response));

                                            $('#submit_button').removeAttr('disabled');
                                        }  

                                    }                     
                                }
                            });
                    }
                }
            });
       

        //transport ajax
             /**   $.ajax({
                    url: '<?php //echo site_url('admin/fare_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + transport_fare_charged,

                    success: function(response) {
                        if(feeding_owe_now_rows > 0 && fare_owe_now_rows > 0) {
                            //current timestamp already exists
                            if(att_status == '2' && fare_owe != 0) {

                                if(fare_owe == 0 || fare_owe == '') {
                                    $('#transport_owe_' + id).val(Number(response));//show amount owe as at today 
                                } else {
                                    $('#transport_owe_' + id).val(fare_owe - transport_fare_charged);//show amount owe as at today 
                                }

                            } else if(att_status == '1') {

                                $.ajax({ //trying to get the fare paid directly from the database
                                    url: '<?php //echo site_url('admin/fare_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + transport_fare_charged + '/'+ 'no',

                                    success: function(t_paid) {


                                        if(fare_owe == 0 || fare_owe == '') { 
                                             $('#transport_paid_' + id).val(t_paid);
                                             
                                             if(t_paid == 0 && response == 0) {
                                                $('#transport_owe_' + id).val(Number(response) + transport_fare_charged);//add today's charge  
                                            } else {
                                                $('#transport_owe_' + id).val(Number(response));//show amount owe as at today  
                                            }
                                        } else {
                                             $('#transport_paid_' + id).val(t_paid);
                                             $('#transport_owe_' + id).val(Number(response));//show amount owe as at today  
                                        }
                                    }
                                });
                            }  
                        } else {
                            //current timestamp does not exist
                            if(att_status == '2') {

                            $('#transport_owe_' + id).val(Number(response) - transport_fare_charged);//just show what the student owes as at today (commulative) 

                            } else if(att_status == '1') {

                                $('#transport_paid_' + id).val(fare_paid);
                                $('#transport_owe_' + id).val(Number(response));//Add today's charge 
                            }  
                        }
                                             
                    }
                }); **/
            }

    //feeding fee amount owe calculator
   // feeding_owe_calculator();

   //hide notifier
   if(counter != undefined)  {
    if(counter == st_id_array.length -1) {
        $('#notifier').slideUp('slow');
    }
   } else {
    $('#notifier').slideUp('slow');
   }
    
}

//to check if a student is marked absent or undefined, if so, disable that student's payment fields
function is_student_present_onload() {
    var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;  
    
    let first_day_timestamp = Number(<?php echo $first_day_timestamp_fc; ?>);
    let timestamp = Number(<?php echo $timestamp; ?>);

    let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
    let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
    let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);
    let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

    for(var i = 0; i < st_id_array.length; i++) {
        let att_status = $('#status_' + st_id_array[i]).val();


        //for feeding
        let feeding_paid = Number($('#feeding_paid_' + st_id_array[i]).val());
        let feeding_owe =  Number($('#feeding_owe_' + st_id_array[i]).val());

        if(feeding_owe > 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
        } else if(feeding_owe == 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
        } else if(feeding_owe < 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
        }

        //for classes
        let classes_paid = Number($('#classes_paid_' + st_id_array[i]).val());
        let classes_owe =  Number($('#classes_owe_' + st_id_array[i]).val());

        if(classes_owe > 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
        } else if(classes_owe == 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
        } else if(classes_owe < 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
        }

        //transport
        /*let fare_paid = Number($('#transport_paid_' + st_id_array[i]).val());
        let fare_owe =  Number($('#transport_owe_' + st_id_array[i]).val());
        let transport_fare_charged = Number($('#transport_fare_charged_' + st_id_array[i]).val());*/



        /*if(fare_owe > 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': '#e73636', 'color': '#fff'});
        } else if(fare_owe == 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': 'green', 'color': '#fff'});
        } else if(fare_owe < 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': '#000', 'color': '#f76262'});
        }*/


        if(att_status != '1') {
            $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#feeding_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#feeding_paid_' + st_id_array[i]).val(''); 

            $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#classes_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#classes_paid_' + st_id_array[i]).val(''); 

            $('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#transport_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#transport_paid_' + st_id_array[i]).val('');
        } else if(att_status == '1') {
            $('#feeding_paid_' + st_id_array[i]).val(feeding_paid);
            $('#feeding_paid_' + st_id_array[i]).removeAttr('readonly');
            $('#feeding_paid_' + st_id_array[i]).removeAttr('placeholder');
            $('#feeding_owe_' + st_id_array[i]).val(feeding_owe);//Add today's charge 

            $('#classes_paid_' + st_id_array[i]).val(classes_paid);
            $('#classes_paid_' + st_id_array[i]).removeAttr('readonly');
            $('#classes_paid_' + st_id_array[i]).removeAttr('placeholder');
            $('#classes_owe_' + st_id_array[i]).val(classes_owe);

            $('#transport_paid_' + st_id_array[i]).val(fare_paid);
            $('#transport_paid_' + st_id_array[i]).removeAttr('readonly');
            $('#transport_paid_' + st_id_array[i]).removeAttr('placeholder');
            $('#transport_owe_' + st_id_array[i]).val(fare_owe);

        }   

        if(first_day_timestamp > timestamp) { //disable all input fields if user selects a date less than the 1st date

            $('#wrong_date_alert').css('display', 'block');

            $('#submit_button').attr('disabled', 'disabled');

            $('#submit_button').click(function(event) {
                return false;
            });

            $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#feeding_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#feeding_paid_' + st_id_array[i]).val(''); 
            $('#feeding_owe_' + st_id_array[i]).attr('readonly', 'true');
            $('#feeding_owe_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#feeding_owe_' + st_id_array[i]).val(''); 
            $('#feeding_owe_' + st_id_array[i]).removeAttr('style');

            $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#classes_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#classes_paid_' + st_id_array[i]).val(''); 
            $('#classes_owe_' + st_id_array[i]).attr('readonly', 'true');
            $('#classes_owe_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#classes_owe_' + st_id_array[i]).val('');
            $('#classes_owe_' + st_id_array[i]).removeAttr('style');

            /*$('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#transport_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#transport_paid_' + st_id_array[i]).val('');
            $('#transport_owe_' + st_id_array[i]).attr('readonly', 'true');
            $('#transport_owe_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#transport_owe_' + st_id_array[i]).val('');
            $('#transport_owe_' + st_id_array[i]).removeAttr('style');*/


        } else {
            $('#wrong_date_alert').css('display', 'none');
        }

        
        //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
        $.ajax({
            url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + st_id_array[i],
            async: false,

            success: function(bf_response) {
                if(bf_response == 'no') {
                    //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
                    if(feeding_fee_charged == 0 || feeding_fee_charged == '' || feeding_fee_charged == null) {
                        //$('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
                    }

                    if(classes_fee_charged == 0 || classes_fee_charged == '' || classes_fee_charged == null) {
                        $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');

                    }

                } else {
                    //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
                    if(feeding_fee_charged_b == 0 || feeding_fee_charged_b == '' || feeding_fee_charged_b == null) {
                      //  $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');


                    }

                    if(classes_fee_charged_b == 0 || classes_fee_charged_b == '' || classes_fee_charged_b == null) {
                        $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');

                    }
                }
            }
        });

        /** if(transport_fare_charged == 0 || transport_fare_charged == '' || transport_fare_charged == null) {
                        $('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            } //end**/
        
    }

}



//updating feeding owe when a student pays an amount
async function student_paid_feeding(id, counter) {

    //check if user has internet access at the moment
    // const hasInternet = await checkOnlineStatus();


    // if(hasInternet == false) {

    //     $('#modal_alert .modal-content').css({
    //         marginTop: window.scrollY + 100 + 'px',
    //     });

    //     $('#submit_button').attr('disabled', 'disabled');
    //     showAjaxModal_alert('<i class="fa fa-info-circle"></i> No internet. Check your internet connectivity!', 'Error');
    //     return false;
    // } //checking for online is done

    //disable submit button on page load and show notifier
    $('#submit_button').attr('disabled', 'disabled');
    $('#notifier').slideDown('slow');
    
    var st_id_array = <?php echo json_encode($st_id_array); ?>;
     var timestamp = <?php echo $timestamp; ?>;


            var class_id = Number(<?php echo $class_id; ?>);
            var previous_day_timestamp = Number(<?php echo $previous_day_timestamp; ?>);
            var att_status = $('#status_' + id).val();

            let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
            let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);


            //check if the current timestamp already exists in the feeding table
            let feeding_owe_now_rows = <?php echo $feeding_owe_now->num_rows(); ?>;
            //let fare_owe_now_rows = <?php //echo $fare_owe_now->num_rows(); ?>;

            //for feeding
            var feeding_owe =  Number($('#feeding_owe_' + id).val());
            var feeding_paid_now = Number($('#feeding_paid_' + id).val());


            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + id,

                success: function(bf_response) {
                    if(bf_response == 'no') {
                        //feeding ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the feeding fee paid directly from the database
                                        url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged + '/' + 'yes',

                                            success: function(fee_owe) {

                                                if(att_status == 1) {
                                                    /*if(fee_paid == 0 || fee_paid == '') {

                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            
                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#feeding_owe_' + id).val((Number(response) + feeding_fee_charged) - feeding_paid_now);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);
                                                            }
                                                        }
                                                    } else {
                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged);
                                                        } else {
                                                            $('#feeding_owe_' + id).val((Number(response)  + feeding_fee_charged) - feeding_paid_now);
                                                        }
                                                    }*/

                                                    $('#feeding_owe_' + id).val((fee_owe - feeding_paid_now) + feeding_fee_charged);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#feeding_paid_' + id).attr('readonly', 'true');
                                                    $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#feeding_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                                    /*$('#transport_paid_' + id).attr('readonly', 'true');
                                                    $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#transport_paid_' + id).val('');
*/
                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                $('#feeding_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            } else {
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');
                                            }
                                        } else if(att_status == 2) {
                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                            /*$('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');*/

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled');
                                        }
                                    }
                                    
                                }
                            });
                    } else {
                       //feeding ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the feeding fee paid directly from the database
                                        url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b + '/' + 'yes',

                                            success: function(fee_owe) {

                                                if(att_status == 1) {
                                                    /*if(fee_paid == 0 || fee_paid == '') {

                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            
                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged_b);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#feeding_owe_' + id).val((Number(response) + feeding_fee_charged_b) - feeding_paid_now);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);
                                                            }
                                                        }
                                                    } else {
                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged_b);
                                                        } else {
                                                            $('#feeding_owe_' + id).val((Number(response)  + feeding_fee_charged_b) - feeding_paid_now);
                                                        }
                                                    }*/

                                                    $('#feeding_owe_' + id).val((fee_owe -feeding_paid_now) + feeding_fee_charged_b);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#feeding_paid_' + id).attr('readonly', 'true');
                                                    $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#feeding_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                                    /*$('#transport_paid_' + id).attr('readonly', 'true');
                                                    $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#transport_paid_' + id).val('');
*/
                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                $('#feeding_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');
                                            } else {
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');
                                            }
                                        } else if(att_status == 2) {
                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                            /*$('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');*/

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled');
                                        }
                                    }
                                    
                                }
                            });
                    }
                }
            });

    //hide notifier
   if(counter != undefined)  {
    if(counter == st_id_array.length -1) {
        $('#notifier').slideUp('slow');
    }
   } else {
    $('#notifier').slideUp('slow');
   }

   $('#modal_alert button').show();

   $('.close').click();
}

//updating classes owe when a student pays an amount
async function student_paid_classes(id, counter) {

    //check if user has internet access at the moment
    // const hasInternet = await checkOnlineStatus();
    // if(hasInternet == false) {

    //     $('#modal_alert .modal-content').css({
    //         marginTop: window.scrollY + 100 + 'px',
    //     });

    //     $('#submit_button').attr('disabled', 'disabled');
    //     showAjaxModal_alert('<i class="fa fa-info-circle"></i> No internet. Check your internet connectivity!', 'Error');
    //     return false;
    // } //checking for online is done


    //disable submit button on page load and show notifier
    $('#submit_button').attr('disabled', 'disabled');
    $('#notifier').slideDown('slow');
    
    var st_id_array = <?php echo json_encode($st_id_array); ?>;
     var timestamp = <?php echo $timestamp; ?>;

            var class_id = Number(<?php echo $class_id; ?>);
            var previous_day_timestamp = Number(<?php echo $previous_day_timestamp; ?>);
            var att_status = $('#status_' + id).val();

            //classes fee

            let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);

            let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);


            //check if the current timestamp already exists in the feeding table
            let classes_owe_now_rows = <?php echo $feeding_owe_now->num_rows(); ?>;
            //let fare_owe_now_rows = <?php //echo $fare_owe_now->num_rows(); ?>;

            //for classes
            var classes_owe =  Number($('#classes_owe_' + id).val());
            var classes_paid_now = Number($('#classes_paid_' + id).val());

            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + id,

                success: function(bf_response) {
                    if(bf_response == 'no') {
                        //classes ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged,

                                success: function(response) {

                                    if(classes_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the classes fee paid directly from the database
                                        url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged + '/' + 'yes',

                                            success: function(class_fee_owe) {

                                                if(att_status == 1) {
                                                    /*if(class_fee_paid == 0 || class_fee_paid == '') {

                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            
                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#classes_owe_' + id).val(Number(response) + classes_fee_charged);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#classes_owe_' + id).val((Number(response) + classes_fee_charged) - classes_paid_now);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                            }
                                                        }

                                                        

                                                    } else {
                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            $('#classes_owe_' + id).val(Number(response) + classes_fee_charged);
                                                        } else {
                                                            $('#classes_owe_' + id).val((Number(response)  + classes_fee_charged) - classes_paid_now);
                                                        }
                                                    }*/
                                                    $('#classes_owe_' + id).val((class_fee_owe -classes_paid_now) + classes_fee_charged);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#classes_paid_' + id).attr('readonly', 'true');
                                                    $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#classes_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#classes_owe_' + id).val(Number(response));

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(classes_paid_now == 0 || classes_paid_now == '') {
                                                $('#classes_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            } else {
                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            }
                                        } else if(att_status == 2) {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#classes_owe_' + id).val(Number(response));

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled'); 
                                        }
                                    }
                                    
                                }
                            });
                    } else {
                        //classes ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b,

                                success: function(response) {

                                    if(classes_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the classes fee paid directly from the database
                                        url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b + '/' + 'yes',

                                            success: function(class_fee_paid) {

                                                if(att_status == 1) {
                                                    /*if(class_fee_paid == 0 || class_fee_paid == '') {

                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            
                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#classes_owe_' + id).val(Number(response) + classes_fee_charged_b);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#classes_owe_' + id).val((Number(response) + classes_fee_charged_b) - classes_paid_now);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                            }
                                                        }
                                                    } else {
                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            $('#classes_owe_' + id).val(Number(response) + classes_fee_charged_b);
                                                        } else {
                                                            $('#classes_owe_' + id).val((Number(response)  + classes_fee_charged_b) - classes_paid_now);
                                                        }
                                                    }*/

                                                    $('#classes_owe_' + id).val((fee_owe -classes_paid_now) + classes_fee_charged_b);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#classes_paid_' + id).attr('readonly', 'true');
                                                    $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#classes_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#classes_owe_' + id).val(Number(response));//subtract today's charge 

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(classes_paid_now == 0 || classes_paid_now == '') {
                                                $('#classes_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            } else {
                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            }
                                        } else if(att_status == 2) {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#classes_owe_' + id).val(Number(response));//subtract today's charge 

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled');

                                        }
                                    }
                                    
                                }
                            });
                    }
                }
            });
        
        //hide notifier
       if(counter != undefined)  {
        if(counter == st_id_array.length -1) {
            $('#notifier').slideUp('slow');
        }
       } else {
        $('#notifier').slideUp('slow');
       }

      $('#modal_alert button').show();

        $('.close').click();
}



//updating transport owe when a student pays an amount
function student_paid_transport(id) {
    let transportPaid = Number($('#transport_paid_' + id).val()) || 0;
    let transportOwe = $('#transport_owe_' + id);
    
    if(transportOwe.length) {
        let currentOwe = Number(transportOwe.attr('data-original-owe')) || Number(transportOwe.val());
        let newOwe = currentOwe - transportPaid;
        transportOwe.val(newOwe);
        
        if(newOwe > 0) {
            transportOwe.css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
        } else if(newOwe == 0) {
            transportOwe.css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
        } else {
            transportOwe.css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
        }
    }
}

//update owe field and color in real-time
function updateOweField(id, type) {
    setTimeout(function() {
        let oweField = $('#' + type + '_owe_' + id);
        
        if(oweField.length) {
            let currentOwe = Number(oweField.val()) || 0;
            
            if(currentOwe > 0) {
                oweField.css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
            } else if(currentOwe == 0) {
                oweField.css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
            } else {
                oweField.css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
            }
        }
    }, 100);
}

//check if attendance status field is empty
$('#attendance_form').submit(async function(event) {

    event.preventDefault();

    // //check if user has internet access at the moment
    // const hasInternet = await checkOnlineStatus();
    // if(hasInternet == false) {

    //     $('#modal_alert .modal-content').css({
    //         marginTop: window.scrollY + 100 + 'px',
    //     });


    //     showAjaxModal_alert('<i class="fa fa-info-circle"></i> No internet. Check your internet connectivity!', 'Error');
    //     return false;
    // } //checking for online is done


    //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

    var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;

    for(var i = 0; i < st_id_array.length; i++) {
        let att_status = $('#status_' + st_id_array[i]).val();

        if(att_status == '' || att_status == null) {
            alert('Some Students\' Attendance Status Is Unknown.\nPlease Make Sure You Set The "Show Entries" Box At The Top Left Corner Of The Form To A Number More Than The Total Number Of Students In The Class, Then Mark Attendance Again.\nE.g. You Can Set It To 25, 50, 100 etc.\nNote: The Default Entry is 10.');
            $('#empty_error').css('display', 'block');
            return false;
        } else {
            $('#empty_error').css('display', 'none');

        }
    }

    //verification of check-in
    verify_check_in();
});


function change_color() {
     var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;


    for(var i = 0; i < st_id_array.length; i++) {
        let att_status = $('#status_' + st_id_array[i]).val();

        //for feeding
        let feeding_owe =  Number($('#feeding_owe_' + st_id_array[i]).val());

        if(feeding_owe > 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
        } else if(feeding_owe == 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
        } else {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
        }

        //for classes
        let classes_owe =  Number($('#classes_owe_' + st_id_array[i]).val());

        if(classes_owe > 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
        } else if(classes_owe == 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
        } else {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
        }

        //transport
        let transport_owe_field = $('#transport_owe_' + st_id_array[i]);
        if(transport_owe_field.length) {
            let fare_owe = Number(transport_owe_field.val());
            if(fare_owe > 0) {
                transport_owe_field.css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
            } else if(fare_owe == 0) {
                transport_owe_field.css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
            } else {
                transport_owe_field.css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
            }
        }
    }
}


var class_selection = "";


function select_section(class_id) {
        if (class_id !== '') {
        $.ajax({
            url: '<?php echo site_url('teacher/get_section/'); ?>' + class_id,
            success:function (response)
            {
                $('#section_holder').html(response);
            }
        });
    }
}

function select_students(class_id) {
    if(class_id !== ''){

        $.ajax({
            url: '<?php echo site_url('admin/get_multi_select_students/'); ?>' + class_id + '/<?=$student_id ?>',
            success:function (response)
            {

            jQuery('#students_holder').slideDown('slow');
            jQuery('#students_holder').html(response);
            initSearchFilter();
            }
        });

    }   else {
        jQuery('#students_holder').slideUp('slow');
    }
}

function initSearchFilter() {
    const searchInput = document.getElementById('student_search');
    const selectAllBtn = document.getElementById('select_all_btn');
    const deselectAllBtn = document.getElementById('deselect_all_btn');
    
    if(searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const studentCards = document.querySelectorAll('.student-card');
            let visibleCount = 0;
            
            studentCards.forEach(card => {
                const studentName = card.dataset.studentName.toLowerCase();
                if(studentName.includes(searchTerm)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            document.getElementById('student_count').textContent = visibleCount;
        });
    }
    
    if(selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.student-card:not([style*="display: none"]) .check').forEach(cb => cb.checked = true);
            updateSelectedCount();
        });
    }
    
    if(deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.check').forEach(cb => cb.checked = false);
            updateSelectedCount();
        });
    }
    
    document.querySelectorAll('.check').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.check:checked').length;
    document.getElementById('selected_count').textContent = selectedCount;
}
    function mark_all_present() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

        $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');
       // var count = <?php echo count($attendance_of_students_array); ?>;
       //enable all the fee selection buttons
       $('.sel_btns').removeAttr('disabled');

       //disable this button
        $('#present_btn').attr('disabled', 'disabled');
        //enable present button
        $('#absent_btn').removeAttr('disabled');

        let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
        let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
        let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);
        let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

        var st_id_array = <?php echo json_encode($st_id_array); ?>;

        for(var i = 0; i < st_id_array.length; i++) {
            $('#status_' + st_id_array[i]).val("1");
            //let transport_fare_charged = Number($('#transport_fare_charged_' + st_id_array[i]).val());
            is_student_present(st_id_array[i], '0', i);

            //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
            if(feeding_fee_charged == 0 || feeding_fee_charged == '' || feeding_fee_charged == null) {
                $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            }

            if(classes_fee_charged == 0 || classes_fee_charged == '' || classes_fee_charged == null) {
                $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            }


            //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
            if(feeding_fee_charged_b == 0 || feeding_fee_charged_b == '' || feeding_fee_charged_b == null) {
                $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            }

            if(classes_fee_charged_b == 0 || classes_fee_charged_b == '' || classes_fee_charged_b == null) {
                $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            }

            /*if(transport_fare_charged == 0 || transport_fare_charged == '' || transport_fare_charged == null) {
                            $('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            } *///end
        }

        $('#modal_alert button').show();

            $('.close').click();

        
    }

    function mark_all_absent() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

            $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');

        //var count = <?php echo count($attendance_of_students_array); ?>;
        ////disable all the fee selection buttons
        $('.sel_btns').attr('disabled', 'disabled');

        //disable this button
        $('#absent_btn').attr('disabled', 'disabled');
        //enable present button
        $('#present_btn').removeAttr('disabled');

        var st_id_array = <?php echo json_encode($st_id_array); ?>;

        for(var i = 0; i < st_id_array.length; i++) {
            $('#status_' + st_id_array[i]).val("2");

            is_student_present(st_id_array[i], '0', i);
        }

        $('#modal_alert button').show();

            $('.close').click();
    }

    //feeding fee
    var counter = 0;
    function mark_all_paid_feeding() {

            $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

            $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');
       // var count = <?php echo count($attendance_of_students_array); ?>;
        var st_id_array = <?php echo json_encode($st_id_array); ?>;

        //feeding
        let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
        let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);

        for(var i = 0; i < st_id_array.length; i++) {

            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + st_id_array[i],
                async: false,
                success: function(bf_response) {

                    if(bf_response == 'no') {
                        $('#feeding_paid_' + st_id_array[i]).val(feeding_fee_charged);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');

                    } else {
                        $('#feeding_paid_' + st_id_array[i]).val(feeding_fee_charged_b);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');
                    }
                }
            });


            
            var att_status = $('#status_' + st_id_array[i]).val();
            student_paid_feeding(st_id_array[i], i);

            if(att_status == '2') {
              is_student_present(st_id_array[i], '0', i);
            }
           
        }       

    }

    //feeding fee
    function mark_all_paid_classes() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

        $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');

        /*showAjaxModal_alert('Please wait...<i class="fa fa-3x fa-spinner fa-pulse"></i>', 'Loading');
        //$('.modal-dialog').css('marginTop', '200px');*/

       // var count = <?php //echo count($attendance_of_students_array); ?>;
        var st_id_array = <?php echo json_encode($st_id_array); ?>;

    //classes
    let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
    let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

        for(var i = 0; i < st_id_array.length; i++) {

            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + st_id_array[i],
                async: false,
                success: function(bf_response) {
                    if(bf_response == 'no') {
                        $('#classes_paid_' + st_id_array[i]).val(classes_fee_charged);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');

                    } else {
                        $('#classes_paid_' + st_id_array[i]).val(classes_fee_charged_b);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');

                    }
                }
            });

            
            var att_status = $('#status_' + st_id_array[i]).val();
            student_paid_classes(st_id_array[i], i);

            if(att_status == '2') {
              is_student_present(st_id_array[i], '0', i);
            }
           
        } 

        //$('.close').click();      
    }

     //feeding fee
    /*function mark_all_paid_transport() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

        $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');

       // var count = <?php //echo count($attendance_of_students_array); ?>;
        var st_id_array = <?php //echo json_encode($st_id_array); ?>;

        for(var i = 0; i < st_id_array.length; i++) {
            let transport_fare_charged = Number($('#transport_fare_charged_' + st_id_array[i]).val());

            $('#transport_paid_' + st_id_array[i]).val(transport_fare_charged);
            var att_status = $('#status_' + st_id_array[i]).val();
            student_paid_transport(st_id_array[i], i);

            if(att_status == '2') {
              is_student_present(st_id_array[i], '0', i);
            }
        }        
    }*/

function check_validation(){
    if(class_selection !== ''){
        $('#submit').removeAttr('disabled')
    }
    else{
        $('#submit').attr('disabled', 'disabled');
    }
}

$('#class_selection').change(function(){
    class_selection = $('#class_selection').val();
    check_validation();
});

$('#time_picker').change(function() {
    $('#submit').removeAttr('disabled');
});




//selecting student for attendance
/*$('#att_selector_form').submit(function(event) {

    let item_checked = $('.check').filter(':checked').length;
    if(item_checked < 1) {

        event.preventDefault();

        //Scroll to the top
          $('html, body').animate({
              scrollTop: ($('#top').offset().top )
          }, 1000); 

        showAjaxModal_alert('No student was selected!', 'Error');
        return false;
    }
});
*/

//ajax
$('#att_selector_form').submit(function(event) {
    /* Act on the event */

    event.preventDefault();

    let item_checked = $('.check').filter(':checked').length;
    if(item_checked < 1) {
        showAjaxModal_alert('No student was selected!', 'Error');
        return false;
    }

    $('#main_page').empty();

    //SHOW LOADER
  $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px; ">Fetching Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');


    $.ajax({
      url: '<?php echo site_url('admin/attendance_selector/'); ?>',
      type: 'POST',
      dataType: 'html',
      data: new FormData(this),
      cache: false,
      contentType: false,
      processData: false
  })
  .done(function(data) {
    if(data == 'promotion error term') {
        showAjaxModal_alert('Make sure students were promoted during the previous term. For further assistance, kindly contact the system administrator.', 'Error');

        navigation('<?php echo site_url('admin/manage_attendance'); ?>');
      
    } else if(data == 'promotion error sem') {
        showAjaxModal_alert('Make sure students were promoted during the previous semester. For further assistance, kindly contact the system administrator.', 'Error');

        navigation('<?php echo site_url('admin/manage_attendance'); ?>')
      
    } else {
        $('#main_page').empty();

        //$('#main_page').html(data);
        navigation(data);

        $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        }); 
        
    }
  });
});

// Auto bill students function
function autoBillStudents() {
    if(confirm('This will automatically bill all students in this class for today. Continue?')) {
        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Processing auto-billing...<br><i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');

        $.ajax({
            url: '<?php echo site_url('admin/auto_bill_students'); ?>',
            type: 'POST',
            data: {
                class_id: <?php echo $class_id; ?>,
                section_id: <?php echo $section_id; ?>,
                timestamp: <?php echo $timestamp; ?>,
                year: '<?php echo $running_year; ?>',
                term: '<?php echo $running_term; ?>'
            },
            success: function(response) {
                var data = JSON.parse(response);
                if(data.status == 'success') {
                    showAjaxModal_alert(data.message, 'Success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showAjaxModal_alert('Error: ' + data.message, 'Error');
                }
            },
            error: function() {
                showAjaxModal_alert('An error occurred during auto-billing', 'Error');
            }
        });
    }
}

// Bulk payment function
function bulkPayment() {
    // Get selected students
    var selectedStudents = [];
    $('.attendance-card').each(function() {
        var studentId = $(this).find('input[name*="feeding_paid_"]').attr('id').replace('feeding_paid_', '');
        if($('#status_' + studentId).val() == '1') { // Only present students
            selectedStudents.push(studentId);
        }
    });

    if(selectedStudents.length === 0) {
        showAjaxModal_alert('No present students found for bulk payment', 'Warning');
        return;
    }

    // Show bulk payment modal
    var modalContent = `
        <div class="p-6">
            <h3 class="text-xl font-bold mb-4">Bulk Payment for ${selectedStudents.length} Students</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Feeding Fee Amount</label>
                    <input type="number" id="bulk_feeding_amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Classes Fee Amount</label>
                    <input type="number" id="bulk_classes_amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Transport Fee Amount</label>
                    <input type="number" id="bulk_transport_amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0.00">
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="$('#modal_alert').modal('hide')" class="px-4 py-2 bg-gray-500 text-white rounded-lg">Cancel</button>
                <button onclick="processBulkPayment(${JSON.stringify(selectedStudents).replace(/"/g, '"')})" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Process Payment</button>
            </div>
        </div>
    `;

    showAjaxModal_alert(modalContent, 'Bulk Payment');
}

// Process bulk payment
function processBulkPayment(studentIds) {
    var feedingAmount = $('#bulk_feeding_amount').val() || 0;
    var classesAmount = $('#bulk_classes_amount').val() || 0;
    var transportAmount = $('#bulk_transport_amount').val() || 0;

    if(feedingAmount == 0 && classesAmount == 0 && transportAmount == 0) {
        alert('Please enter at least one payment amount');
        return;
    }

    $('#modal_alert .modal-content').html('<center><div style="font-size: 20px; font-weight: bolder; margin: 50px 0;">Processing bulk payment...<br><i class="fa fa-spinner fa-pulse"></i></div></center>');

    $.ajax({
        url: '<?php echo site_url('admin/process_bulk_payment'); ?>',
        type: 'POST',
        data: {
            student_ids: studentIds,
            feeding_amount: feedingAmount,
            classes_amount: classesAmount,
            transport_amount: transportAmount,
            timestamp: <?php echo $timestamp; ?>,
            class_id: <?php echo $class_id; ?>,
            section_id: <?php echo $section_id; ?>
        },
        success: function(response) {
            var data = JSON.parse(response);
            if(data.status == 'success') {
                showAjaxModal_alert(data.message, 'Success');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showAjaxModal_alert('Error: ' + data.message, 'Error');
            }
        },
        error: function() {
            showAjaxModal_alert('An error occurred during bulk payment', 'Error');
        }
    });
}

// Get student billing info
function getStudentBillingInfo(studentId) {
    $.ajax({
        url: '<?php echo site_url('admin/get_student_billing_info'); ?>',
        type: 'POST',
        data: {
            student_id: studentId,
            timestamp: <?php echo $timestamp; ?>,
            class_id: <?php echo $class_id; ?>
        },
        success: function(response) {
            var data = JSON.parse(response);
            if(data.status == 'success') {
                var info = `
                    <div class="p-4">
                        <h4 class="font-bold mb-3">Student Billing Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p><strong>Feeding Charged:</strong> GHS ${data.feeding_charged}</p>
                                <p><strong>Feeding Due:</strong> GHS ${data.feeding_due}</p>
                            </div>
                            <div>
                                <p><strong>Classes Charged:</strong> GHS ${data.classes_charged}</p>
                                <p><strong>Classes Due:</strong> GHS ${data.classes_due}</p>
                            </div>
                        </div>
                        <p class="mt-2"><strong>Benefit Category:</strong> ${data.benefit_category}</p>
                    </div>
                `;
                showAjaxModal_alert(info, 'Billing Info');
            } else {
                showAjaxModal_alert('Error: ' + data.message, 'Error');
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to load billing information', 'Error');
        }
    });
}

// Filter attendance cards function
function filterAttendanceCards(searchValue) {
    const searchTerm = searchValue.toLowerCase().trim();
    const cards = document.querySelectorAll('.attendance-card');
    cards.forEach(function(card) {
        const studentName = (card.getAttribute('data-student-name') || '').toLowerCase();
        const studentCode = (card.getAttribute('data-student-code') || '').toLowerCase();
        if(studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

</div>
