<?php 

//feedback
$display = 'none';
$alert_type = 'success';
if(isset($_GET['msg'])) {
    $msg_val = $_GET['msg'];
    if($msg_val == 1) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = 'Did You Change This Student\'s ID No? Duplicate Found. Please Maintain The Old ID No!';
    } else if($msg_val == 2) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = 'Could Not Update. Invalid Email Found!';
    } else if($msg_val == 3) {
        $display = 'block';
        $alert_type = 'success';
        $feedback = 'Student\'s Information Updated Successfully';
    } else if($msg_val == 4) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = 'Did You Change This Student\'s Email? Duplicate Found. Please Maintain The Old Email Address!';
    } else if($msg_val == 5) {
        $display = 'block';
        $alert_type = 'success';
        $feedback = 'Student\'s Information Updated Successfully';
    } else {
        $feedback = '';
    }
} else {
    $feedback = '';
}

    $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
    $skin_colour = $this->db->get_where('settings', array('type' => 'skin_colour'))->row()->description;

    $name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
    $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;

    $section_id = $this->db->get_where('section', array('class_id' => $class_id))->row()->section_id;
?>

<style type="text/css">
    #search_row {
        position: sticky !important; 
        top: 0px !important; 
        z-index: 999 !important;
    }
    .datatable tbody tr {
        position: relative;
    }
    .datatable tbody td {
        position: relative;
    }

    /* Action Popup Menu Styles */
    .action-popup-wrapper {
        position: relative;
        display: inline-block;
    }
    
    .action-popup-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .action-popup-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .action-popup-btn i.fa-chevron-down {
        font-size: 10px;
        transition: transform 0.2s;
    }
    
    .action-popup-btn.active i.fa-chevron-down {
        transform: rotate(180deg);
    }

    /* Status button variants */
    .action-popup-btn.status-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .action-popup-btn.status-blocked {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }
    
    .action-popup-btn.status-muted {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .action-popup-menu {
        position: fixed;
        top: 0;
        left: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2), 0 0 0 1px rgba(0,0,0,0.05);
        min-width: 200px;
        z-index: 99999;
        opacity: 0;
        visibility: hidden;
        transform: scale(0.95) translateY(-5px);
        transition: all 0.15s ease;
        overflow: hidden;
        padding: 8px 0;
        pointer-events: none;
    }
    
    .action-popup-menu.show {
        opacity: 1;
        visibility: visible;
        transform: scale(1) translateY(0);
        pointer-events: auto;
    }
    
    .action-popup-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        text-decoration: none;
        transition: all 0.15s;
        border: none;
        background: none;
        width: 100%;
        cursor: pointer;
        text-align: left;
    }
    
    .action-popup-item:hover {
        background: #f3f4f6;
    }
    
    .action-popup-item i {
        width: 18px;
        text-align: center;
        font-size: 14px;
    }
    
    .action-popup-item.success i { color: #059669; }
    .action-popup-item.success:hover { background: #d1fae5; color: #047857; }
    
    .action-popup-item.danger i { color: #dc2626; }
    .action-popup-item.danger:hover { background: #fee2e2; color: #b91c1c; }
    
    .action-popup-item.warning i { color: #d97706; }
    .action-popup-item.warning:hover { background: #fef3c7; color: #b45309; }
    
    .action-popup-item.primary i { color: #2563eb; }
    .action-popup-item.primary:hover { background: #dbeafe; color: #1d4ed8; }
    
    .action-popup-item.info i { color: #0891b2; }
    .action-popup-item.info:hover { background: #cffafe; color: #0e7490; }
    
    .action-popup-item.purple i { color: #7c3aed; }
    .action-popup-item.purple:hover { background: #ede9fe; color: #6d28d9; }
    
    .action-popup-divider {
        height: 1px;
        background: #e5e7eb;
        margin: 6px 0;
    }
</style>
<hr />

<!-- Class Name Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">
            <?php 
                $class_info = $this->db->get_where('class', array('class_id' => $class_id))->row();
                echo $class_info->name . ' ' . $class_info->name_numeric;
                $section_info = $this->db->get_where('section', array('class_id' => $class_id))->row();
                if($section_info) {
                    echo ' - ' . $section_info->name;
                }
            ?>
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-md-12">

        <ul class="nav nav-tabs bordered md:p-5">
            <li class="active">
                <a href="#home" data-toggle="tab">
                    <span class="visible-xs"><i class="entypo-users"></i></span>
                    <span class=""><?php echo 'All Students';?></span>
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
            <?php if($admin_level != 4): // Hide buttons for cashiers ?>
            <li class="flex flex-col w-full max-w-full">
                <!-- Mobile Layout: 2 rows -->
                <div class="md:hidden">
                    <!-- Row 1: Bulk Marksheets & Cummulative Reports -->
                    <div class="grid grid-cols-2 gap-3 p-5 shadow-lg border-t-2 border-sky-500 mt-5 rounded-t-lg">
                        <div class="w-full">
                            <a href="#panel_exam" data-toggle="collapse" id="choose_exam"
                            class="btn rounded-lg btn-danger w-full h-14 text-sm font-bold flex items-center justify-center" target="_blank">
                                <i class="entypo-chart-line mr-1"></i>
                                <span>Bulk Marksheets</span>
                            </a>
                        </div>
                        <div class="w-full">
                            <a href="<?php echo site_url('admin/bulk_cummulative_reports/'.$class_id); ?>"
                            class="btn rounded-lg btn-success w-full h-14 text-sm font-bold flex items-center justify-center" target="_blank">
                                <i class="entypo-chart-bar mr-1"></i>
                                <span>Cummulative</span>
                            </a>
                        </div>
                    </div>
                    <!-- Row 2: Print Bills, Admit Student, Print Info -->
                    <div class="grid grid-cols-3 gap-3 px-5 pb-5">
                        <div class="w-full">
                            <a href="<?php echo site_url('admin/terminal_bills_selection?class_id='.$class_id); ?>"
                            class="btn rounded-lg btn-success w-full h-14 text-sm font-bold flex items-center justify-center" target="_blank">
                                <i class="entypo-credit-card mr-1"></i>
                                <span>Bills</span>
                            </a>
                        </div>
                        <div class="w-full">
                            <a href="#" onclick="navigation('<?php echo site_url('admin/student_add');?>')"
                            class="btn rounded-lg btn-primary w-full h-14 text-sm font-bold flex items-center justify-center">
                                <i class="entypo-plus-circled mr-1"></i>
                                <span>Admit</span>
                            </a>
                        </div>
                        <div class="w-full">
                            <?php
                                if($class_name == 'JHSS') {
                                    ?>
                                        <a href="<?php echo site_url('admin/student_information_print/'.$class_id.'/'.$running_year.'/'.$running_sem);?>"
                            class="btn rounded-lg btn-info w-full h-14 text-sm font-bold flex items-center justify-center" target="_blank">
                                    <?php
                                } else {
                                    ?>
                                        <a href="<?php echo site_url('admin/student_information_print/'.$class_id.'/'.$running_year.'/'.$running_term);?>"
                            class="btn rounded-lg btn-info w-full h-14 text-sm font-bold flex items-center justify-center" target="_blank">
                                    <?php
                                }
                            ?>
                                <i class="entypo-print mr-1"></i>
                                <span>Info</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desktop Layout: Original 5 columns -->
                <div class="d-none d-md-grid" style="display: none; grid-template-columns: repeat(5, 1fr); gap: 0.75rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-top: 2px solid #0ea5e9; margin-top: 3rem; border-radius: 0.5rem;">
                    <style>
                        @media (min-width: 768px) {
                            .d-md-grid { display: grid !important; }
                        }
                    </style>
                    <div class="w-full">
                        <a href="#panel_exam" data-toggle="collapse" id="choose_exam"
                        class="btn rounded-lg btn-danger w-full h-16 text-xl font-bold flex items-center justify-center" target="_blank">
                            <i class="entypo-chart-line mr-2"></i>
                            <span>Bulk Marksheets</span>
                        </a>
                    </div>
                    <div class="w-full">
                        <a href="<?php echo site_url('admin/terminal_bills_selection?class_id='.$class_id); ?>"
                        class="btn rounded-lg btn-success w-full h-16 text-xl font-bold flex items-center justify-center" target="_blank">
                            <i class="entypo-credit-card mr-2"></i>
                            <span>Print Bills</span>
                        </a>
                    </div>
                    <div class="w-full">
                        <a href="#" onclick="navigation('<?php echo site_url('admin/student_add');?>')"
                        class="btn rounded-lg btn-primary w-full h-16 text-xl font-bold flex items-center justify-center">
                            <i class="entypo-plus-circled mr-2"></i>
                            <span>Admit Student</span>
                        </a>
                    </div>
                    <div class="w-full">
                        <?php
                            if($class_name == 'JHSS') {
                                ?>
                                    <a href="<?php echo site_url('admin/student_information_print/'.$class_id.'/'.$running_year.'/'.$running_sem);?>"
                        class="btn rounded-lg btn-info w-full h-16 text-xl font-bold flex items-center justify-center" target="_blank">
                                <?php
                            } else {
                                ?>
                                    <a href="<?php echo site_url('admin/student_information_print/'.$class_id.'/'.$running_year.'/'.$running_term);?>"
                        class="btn rounded-lg btn-info w-full h-16 text-xl font-bold flex items-center justify-center" target="_blank">
                                <?php
                            }
                        ?>
                            <i class="entypo-print mr-2"></i>
                            <span>Print Info</span>
                        </a>
                    </div>
                    <div class="w-full">
                        <a href="<?php echo site_url('admin/bulk_cummulative_reports/'.$class_id); ?>"
                        class="btn rounded-lg btn-success w-full h-16 text-xl font-bold flex items-center justify-center" target="_blank">
                            <i class="entypo-chart-bar mr-2"></i>
                            <span>Generate Cummulative Reports</span>
                        </a>
                    </div>
                </div>

                <div class="panel panel-info panel-collapse collapse mt-12 shadow-md" id="panel_exam">
                    <div class="panel-body w-full">
                        <?php 
                         $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
                        $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

                        if($class_name == 'CRECHE') {
                            echo form_open(site_url('admin/student_marksheet_bulk_print_view_creche/'. $class_id. '/'. $row['section_id'])); 
                        } else {
                            echo form_open(site_url('admin/student_marksheet_bulk_print_view/'. $class_id. '/'. $row['section_id']), array('id' => 'exam_selected_form' )); 
                        }
                        

                        ?>


                        <div class="flex flex-col md:flex-row gap-8 md:gap-5 justify-around md:items-center w-full max-w-full p-10 md:p-5">
                            <label for="to_class_id" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white w-full max-w-full md:w-1/5 md:max-w-1/5 uppercase">Select Exam Type</label>
                            <select id="exam_id" name="exam_id" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-full max-w-full md:w-2/5 md:max-w-2/5 h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="">CHOOSE EXAMINATION</option>
                                <?php 
                                $this->db->select('exam_id');
                                $this->db->distinct();
                                $exams_query = $this->db->get_where('mark', array('class_id' => $class_id));
                                

                                if($class_name == 'JHSS') {
                                    if($exams_query->num_rows() > 0) {
                                    $exams = $exams_query->result_array();
                                    
                                    // Fetch exam details and sort by year and semester descending
                                    $exams_with_details = array();
                                    foreach($exams as $e_row) {
                                        $ex_q = $this->db->get_where('exam', array('exam_id' => $e_row['exam_id']))->row();
                                        if($ex_q) {
                                            $exams_with_details[] = array(
                                                'exam_id' => $e_row['exam_id'],
                                                'name' => $ex_q->name,
                                                'year' => $ex_q->year,
                                                'sem' => $ex_q->sem
                                            );
                                        }
                                    }
                                    
                                    // Sort by year desc, then semester desc
                                    usort($exams_with_details, function($a, $b) {
                                        $year_cmp = strcmp($b['year'], $a['year']);
                                        if($year_cmp != 0) return $year_cmp;
                                        return $b['sem'] - $a['sem'];
                                    });
                                    
                                    foreach($exams_with_details as $exam):
                                         ?>
                                    
                                    <option value="<?php echo $exam['exam_id']; ?>"><?php echo $exam['name']. ', Year: '. explode('-', $exam['year'])[1].' | Semester: '.$exam['sem']; ?></option>

                                    <?php endforeach; 
                                    } else {
                                        echo '<option>No exam found</option>';
                                    }

                                } else {

                                    if($exams_query->num_rows() > 0) {
                                        $exams = $exams_query->result_array();
                                        
                                        // Fetch exam details and sort by year and term descending
                                        $exams_with_details = array();
                                        foreach($exams as $e_row) {
                                            $ex_q = $this->db->get_where('exam', array('exam_id' => $e_row['exam_id']))->row();
                                            if($ex_q) {
                                                $exams_with_details[] = array(
                                                    'exam_id' => $e_row['exam_id'],
                                                    'name' => $ex_q->name,
                                                    'year' => $ex_q->year,
                                                    'term' => $ex_q->term
                                                );
                                            }
                                        }
                                        
                                        // Sort by year desc, then term desc
                                        usort($exams_with_details, function($a, $b) {
                                            $year_cmp = strcmp($b['year'], $a['year']);
                                            if($year_cmp != 0) return $year_cmp;
                                            return $b['term'] - $a['term'];
                                        });
                                        
                                        foreach($exams_with_details as $exam): 
                                            ?>
                                    
                                    <option value="<?php echo $exam['exam_id']; ?>"><?php echo $exam['name']. ', Year: '. explode('-', $exam['year'])[1].' | Term: '.$exam['term']; ?></option>
                                    <?php endforeach; 
                                    } else {
                                        echo '<option>No exam found</option>';
                                    }

                                }
                                    
                                    ?>
                            </select>
                            <input type="submit" class="btn rounded-lg btn-info h-20 text-2xl font-bold uppercase" id="c_print" name="print_b" value="Click here to print">
                        </div>
                            
                            
                            <?php echo form_close(); ?>
                            <a target="_blank" id="exam_anchor"></a>

                            <script type="text/javascript">
                                $('#exam_selected_form').submit(function(e) {
                                    e.preventDefault();
                                    let exam_id = $('#exam_id').val();

                                    $('#exam_anchor').attr('href', '<?=site_url('admin/student_marksheet_bulk_print_view/'. $class_id. '/'. $row['section_id']);?>/' + exam_id);
                                    $('#exam_anchor')[0].click();
                                    //fold up the panel
                                    $('#choose_exam')[0].click();
                                });
                            </script>
                    </div>
                </div> 
            </li>
            <?php endif; // End cashier check ?>
        </ul>

        <div class="tab-content">
            <div style="display: <?= $display; ?>; margin-top: 10px" class="alert alert-<?= $alert_type; ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
               <strong> <?php echo $feedback; ?></strong>
            </div>
            <hr>
                <div class="bg-gray-100 md:h-40 p-6 mb-16 md:mb-0 h-fit md:h-auto">
                
                    <!-- <div class="form-group row" id="search_row">
                        <div class="col-lg-9 col-md-9 col-sm-7"></div>
                        <div class="col-lg-3 col-md-3 col-sm-5">
                            <input type="search" class="form-control bg-gray-50 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" name="student_search" id="student_search" placeholder="Search Student..." style="height: 35px">
                        </div>
                        
                    </div> -->

                    <div class="flex flex-col md:flex-row gap-4 justify-around divide-y-4 md:divide-x-4 divide-gray-50">
                        <div class="flex justify-between"><span class="text-gray-500 text-2xl font-semibold">TOTAL MALES:</span> <span class="font-bold text-3xl" id="total_males"><span class="text-sm text-gray-400">Updating...</span></span></div>

                        <div class="flex justify-between"><span class="text-gray-500 text-2xl font-semibold">TOTAL FEMALES:</span> <span class="font-bold text-3xl" id="total_females"><span class="text-sm text-gray-400">Updating...</span></span></div>

                        <div class="flex justify-between"><span class="text-gray-500 text-2xl font-semibold">UNSET GENDER:</span> <span class="font-bold text-3xl" id="total_unset_gender"><span class="text-sm text-gray-400">Updating...</span></span></div>

                        <?php 
                            if($boarding_system == 'yes'):
                                ?>
                            <div class="flex justify-between"><span class="text-gray-500 text-2xl font-semibold">TOTAL BOARDERS:</span> <span class="font-bold text-3xl" id="total_boarders"><?=number_format($this->boarding_model->classBoardersCount($class_id), 0, '.', ',');?></span></div>

                            <div class="flex justify-between"><span class="text-gray-500 text-2xl font-semibold">TOTAL DAY STUDENTS:</span> <span class="font-bold text-3xl"><?=number_format($this->boarding_model->classDayStudentsCount($class_id), 0, '.', ',');?></span></div>
                        <?php
                            endif;
                        ?>
                    </div>
                </div>
            <hr>

            <div class="tab-pane active mt-8 md:mt-0 overflow-x-scroll" id="home">
                <?php echo form_open(site_url('admin/bulk_students_delete/'. $class_id), array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'checkboxes_form', 'enctype' => 'multipart/form-data'));?>

                <table class="w-full text-xl text-left text-gray-500 dark:text-gray-400 datatable" id="">
                  <thead class="text-xl text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                      <tr>
                          <th scope="col" class="p-4"></th>
                          <th scope="col" class="px-4 py-3">S/N</th>
                          <th scope="col" class="px-4 py-3">ID No</th>
                          <th scope="col" class="px-4 py-3">Name</th>
                          <th scope="col" class="px-4 py-3">Gender</th>
                          <th scope="col" class="px-4 py-3">Residence Type</th>
                          <?php if($admin_level != 4): // Show for non-cashiers ?>
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Auth Key</th>
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Account Status</th>
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Option</th>
                          <?php else: // Show for cashiers ?>
                          <th scope="col" class="px-4 py-3">Guardian Name</th>
                          <th scope="col" class="px-4 py-3">Guardian Contact</th>
                          <th scope="col" class="px-4 py-3 text-center">View</th>
                          <?php endif; ?>
                      </tr>
                    </thead>
                    <tbody>

                        <?php   
                                if($class_name == 'JHSS') {
                                    $student_query = $this->db->get_where('enroll' , array(
                                    'class_id' => $class_id, 'mute' => '0', 'year' => $running_year, 'sem' => $running_sem
                                ));
                                    $students   =   $student_query->result_array();

                                } else {
                                    $student_query = $this->db->get_where('enroll' , array(
                                    'class_id' => $class_id , 'year' => $running_year, 'mute' => '0', 'term' => $running_term
                                ));
                                    $students   =   $student_query->result_array();
                                }

                             $class_male = 0; 
                             $class_female = 0;
                             $class_unknown = 0;
                             $serial_number = 1; // Initialize serial number counter
                                
                            if($student_query->num_rows() > 0):
                                foreach($students as $row):?>

                                <?php 
                                    $student_info = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row();

                                    $students_acc_st = $student_info->block_limit;

                                    $students_mute = $student_info->mute;

                                    $enrollmentRow = $this->crud_model->getStudentCurrentEnrollmentStatusRow($row['student_id']);

                                    $gender = $student_info->sex;

                                    $sGender = strtolower($gender);
                                    $studentOwes = $this->financial_report_model->getAllBillInvoicesOwingByStudentId($row['student_id']);

                                    if($sGender == 'female') {

                                        $class_female++;

                                    } else if($sGender == 'male') {

                                        $class_male++;

                                    } else {
                                        
                                        $class_unknown++;
                                    }

                                    if($students_acc_st == 3) {
                                        $as_btn = 'Blocked';
                                        $btn_style = 'blocked';

                                        if($students_mute == 1) {
                                            $as_btn = 'Blocked & Muted';
                                            $status_actions = array(
                                                array('action' => 'unblock', 'icon' => 'fa fa-unlock', 'label' => 'Unblock', 'class' => 'success'),
                                                array('action' => 'unmute', 'icon' => 'glyphicon glyphicon-ok', 'label' => 'Unmute', 'class' => 'success')
                                            );
                                        } else {
                                            $status_actions = array(
                                                array('action' => 'unblock', 'icon' => 'fa fa-unlock', 'label' => 'Unblock', 'class' => 'success'),
                                                array('action' => 'mute', 'icon' => 'glyphicon glyphicon-remove', 'label' => 'Mute', 'class' => 'danger')
                                            );
                                        }
                                    } else {
                                        $as_btn = 'Active';
                                        $btn_style = 'active';
                                        $status_actions = array(
                                            array('action' => 'block', 'icon' => 'glyphicon glyphicon-lock', 'label' => 'Block', 'class' => 'danger'),
                                            array('action' => 'mute', 'icon' => 'glyphicon glyphicon-remove', 'label' => 'Mute', 'class' => 'warning')
                                        );

                                        if($students_mute == 1) {
                                            $as_btn = 'Muted';
                                            $btn_style = 'muted';
                                            $status_actions = array(
                                                array('action' => 'block', 'icon' => 'glyphicon glyphicon-lock', 'label' => 'Block', 'class' => 'danger'),
                                                array('action' => 'unmute', 'icon' => 'glyphicon glyphicon-ok', 'label' => 'Unmute', 'class' => 'success')
                                            );
                                        }
                                    }

                                // Build popup menu button for account status
                                $account_status = '<div class="action-popup-wrapper">';
                                $account_status .= '<button type="button" class="action-popup-btn status-'.$btn_style.'" onclick="toggleStatusMenu(this, event, '.$row['student_id'].')">';
                                $account_status .= $as_btn.' <i class="fa fa-chevron-down"></i>';
                                $account_status .= '</button></div>';
                                ?>
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                          <td class="px-4 py-3">
                            <input type="checkbox" class="checkbox" onclick="boxChecked()" name="students_sel[]" value="<?= $row['student_id']; ?>"></td>
                            <td class="px-4 py-3 font-semibold"><?php echo $serial_number++; ?></td>
                            <td><?php echo $student_info->student_code;?></td>
                          


                            <td scope="row" class="flex items-center content-center px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white" width="80">
                              <img src="<?php echo $this->crud_model->get_image_url('student',$row['student_id'], $gender);?>" alt="Student image" class="w-auto h-8 mr-3 img-circle" width="30">
                              <div>
                                  <?php
                                    echo $this->db->get_where('student' , array(
                                        'student_id' => $row['student_id']
                                    ))->row()->name;
                                ?>
                              </div>
                          </td>

                          <td class="px-4 py-3">
                              <div>
                                  <?php
                                    echo ucwords(strtolower($gender));
                                ?>
                              </div>
                          </td>

                            <!-- <td class="px-4 py-3">
                                <?php
                                    //echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->address;
                                ?>
                            </td> -->
                            <td class="px-4 py-3">
                                <?=$enrollmentRow->residence_type; ?>
                            </td>
                            
                            <?php if($admin_level != 4): // Show for non-cashiers ?>
                            <td class="px-4 py-3 text-center" style="font-weight: bolder; letter-spacing: 3px;" width="100">
                             <?php
                                    echo $this->db->get_where('student' , array(
                                        'student_id' => $row['student_id']
                                    ))->row()->authentication_key;
                                ?>
                            </td>

                            <td class="px-4 py-3 text-center" style="font-weight: bolder; letter-spacing: 3px;" width="150">
                             <?php
                                   echo $account_status;
                                ?>
                            </td>
                            <td class="px-4 py-3 text-center" width="100">

                                <div class="action-popup-wrapper">
                                    <button type="button" class="action-popup-btn" onclick="toggleActionMenu(this, event, <?=$row['student_id']?>)">
                                        Actions <i class="fa fa-chevron-down"></i>
                                    </button>
                                </div>
                                <!-- Hidden data attributes for actions -->
                                <span class="hidden action-data" 
                                      data-student-id="<?= $row['student_id']; ?>"
                                      data-class-id="<?= $class_id; ?>"
                                      data-marksheet-url="<?= $class_name == 'CRECHE' ? site_url('admin/student_marksheet_creche/'.$row['student_id']) : site_url('admin/student_marksheet/'.$row['student_id']); ?>"
                                      data-sms-url="<?= site_url('admin/message/sms_send?si='.$row['student_id']); ?>"
                                      data-profile-url="<?= site_url('admin/student_profile/'.$row['student_id']); ?>"
                                      data-edit-url="<?= site_url('modal/popup/modal_student_edit/'.$row['student_id']); ?>"
                                      data-id-url="<?= site_url('modal/popup/student_id/'.$row['student_id']); ?>"
                                      data-has-invoice="<?= count($studentOwes) > 0 ? '1' : '0'; ?>"></span>

                            </td>
                            <?php else: // Show for cashiers
                                // Get guardian information
                                $parent_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->parent_id;
                                $guardian_name = 'N/A';
                                $guardian_phone = 'N/A';
                                if($parent_id) {
                                    $parent_info = $this->db->get_where('parent', array('parent_id' => $parent_id))->row();
                                    if($parent_info) {
                                        $guardian_name = $parent_info->name;
                                        $guardian_phone = $parent_info->phone;
                                    }
                                }
                            ?>
                            <td class="px-4 py-3"><?php echo $guardian_name; ?></td>
                            <td class="px-4 py-3"><?php echo $guardian_phone; ?></td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" onclick="event.stopPropagation(); showCashierStudentView(<?php echo $row['student_id']; ?>)" class="btn btn-sm btn-info rounded-lg" style="font-weight: 600;">
                                    <i class="entypo-user"></i> View Details
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach;
                    else:
                        /*echo '<tr>
                            <td colspan="9" align="center">
                               No Record Found!
                            </td>
                            </tr>';*/
                    endif;
                        ?>
                    </tbody>
                    <tfoot id="tfooter" style="display: none">
                        <tr>
                            <td colspan="8" align="center">
                                <div id="action_bar" class="fixed bottom-0 left-0 right-0 shadow-2xl border-t-4 border-white transform translate-y-full transition-all duration-500 ease-out z-50">
                                    <div class="container mx-auto px-3 md:px-6 py-3 md:py-4">
                                        <div class="flex flex-col md:flex-row items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 md:gap-4">
                                                <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-3">
                                                    <i class="entypo-check text-white text-xl md:text-2xl"></i>
                                                </div>
                                                <div class="text-white">
                                                    <p class="text-xs md:text-sm font-medium opacity-90">Selected</p>
                                                    <p class="text-xl md:text-2xl font-bold" id="selected_count">0</p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-<?php echo $admin_level == 1 ? '3' : '2'; ?> md:flex gap-2 md:gap-3 w-full md:w-auto">
                                                <a href="#" id="move_student" class="bg-white hover:bg-gray-100 text-blue-600 px-3 py-3 md:px-8 md:py-4 rounded-lg font-bold text-sm md:text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2 whitespace-nowrap">
                                                    <i class="entypo-shuffle text-lg md:text-2xl"></i>
                                                    <span>Move Students</span>
                                                </a>
                                                <a href="#" id="change_residence_status" class="bg-white hover:bg-gray-100 text-purple-600 px-3 py-3 md:px-8 md:py-4 rounded-lg font-bold text-sm md:text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2 whitespace-nowrap">
                                                    <i class="entypo-home text-lg md:text-2xl"></i>
                                                    <span>Change Residence</span>
                                                </a>
                                                <?php if($admin_level == 1): ?>
                                                <button type="submit" name="submit_delete" id="submit_delete" class="bg-red-500 hover:bg-red-600 text-white px-3 py-3 md:px-8 md:py-4 rounded-lg font-bold text-sm md:text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 cursor-pointer flex items-center justify-center gap-2 whitespace-nowrap">
                                                    <i class="entypo-trash text-lg md:text-2xl"></i>
                                                    <span>Delete Selected</span>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>

            </div>

        </div>


    </div>
</div>


<script type="text/javascript">
    var dtable;
    jQuery(document).ready(function($) {

     /*   dtable = $('.datatable').DataTable({
            paging: false,
        });
    */

    $('#total_males').text(<?=number_format($class_male, 0, '.', ',') ?>);
    $('#total_females').text(<?=number_format($class_female, 0, '.', ',') ?>);
    $('#total_unset_gender').text(<?=number_format($class_unknown, 0, '.', ',') ?>);

        $('.datatable').DataTable({
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
        });
        
        
        boxChecked();

        
    });

    $('input[name="students_sel[]"]').click(function(e) {
            
        let selected_ids = [];

        $('input[name="students_sel[]"]:checked').each(function() {
            selected_ids.push($(this).val());
        });

        $('#move_student').attr("onclick", "showAjaxModal_move_student('<?php echo site_url('modal/popup/modal_move_student/'.$class_id);?>/', [" + selected_ids + "])" )

        $('#change_residence_status').attr("onclick", "showAjaxModal_residence_status('<?php echo site_url('modal/popup/modal_change_residence_status/'.$class_id);?>/', [" + selected_ids + "])" )

    });

    //to filter the students
    $("#student_search").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $(".datatable tbody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
      });
    });

    $('#c_print').click(function(event) {
        var exam_id = $('#exam_id').val();
        if(exam_id == '' || exam_id == null) {
            toastr.error('No exam was selected yet');
            return false;
        }
    });

    //is any box checked
    function boxChecked() {
        let checkboxes = $('#checkboxes_form td input[type="checkbox"]');
        let count_checked_buttons = checkboxes.filter(':checked').length;

        if(count_checked_buttons < 1) {
            $('#tfooter').css('display', 'none');
            $('#action_bar').removeClass('translate-y-0').addClass('translate-y-full');
        } else {
            $('#tfooter').removeAttr('style');
            $('#selected_count').text(count_checked_buttons);
            
            // Apply theme color from PHP variable
            var skinColor = '<?php echo $skin_colour; ?>';
            var bgColor = '#522b76'; // default purple
            
            if(skinColor === 'blue') bgColor = '#2c3e50';
            else if(skinColor === 'red') bgColor = '#c0392b';
            else if(skinColor === 'green') bgColor = '#27ae60';
            else if(skinColor === 'yellow') bgColor = '#f39c12';
            else if(skinColor === 'black') bgColor = '#34495e';
            else if(skinColor === 'white') bgColor = '#95a5a6';
            else if(skinColor === 'cafe') bgColor = '#8b4513';
            
            $('#action_bar').css('background', bgColor);
            
            setTimeout(function() {
                $('#action_bar').removeClass('translate-y-full').addClass('translate-y-0');
            }, 100);
        }
    }

    $('#checkboxes_form').submit(function(event) {
        event.preventDefault();
        let admin_level = <?php echo $admin_level; ?>;
        
        if(admin_level != 1) {
            showAjaxModal_alert('Sorry, you are not allowed to perform this action. Contact your Super Administrator.', 'error');
            return false;
        }
        
        showConfirmModal(
            'Confirm Bulk Delete',
            'Are you sure you want to delete the selected students? This action is irreversible!',
            function() {
                showAjaxModal_alert('Deleting students...', 'loading');
                $.ajax({
                    url: $('#checkboxes_form').attr('action'),
                    type: 'POST',
                    data: $('#checkboxes_form').serialize(),
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success');
                    } else {
                        showAjaxModal_alert(response.message || 'Deletion failed', 'error');
                    }
                }).fail(function() {
                    showAjaxModal_alert('An error occurred while deleting students', 'error');
                });
            },
            'Delete',
            'danger'
        );
    });


    function deleteStudent(student_id, class_id) {

        showConfirmModal(
            'Confirm Delete',
            'Are you sure you want to permanently delete this student? This action cannot be undone!',
            function() {
                showAjaxModal_alert('Deleting student...', 'loading');
                $.ajax({
                    url: '<?php echo site_url('admin/delete_student/'); ?>' + student_id + '/' + class_id,
                    type: 'GET',
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success');
                    } else {
                        showAjaxModal_alert(response.message, 'error');
                    }
                }).fail(function() {
                    showAjaxModal_alert('An error occurred while deleting student', 'error');
                });
            },
            'Delete',
            'danger'
        );
        return false;
    }

    function account_block(student_id) {
        var class_id = '<?php echo $class_id; ?>';
        showConfirmModal(
            'Block Student Account',
            'Are you sure you want to block this student account?',
            function() {
                showAjaxModal_alert('Blocking account...', 'loading');
                $.ajax({
                    url: '<?php echo site_url('admin/student/block/');?>' + student_id + '/' + class_id,
                    type: 'GET',
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message || 'Student account blocked successfully', 'success');
                        location.reload();
                    } else {
                        showAjaxModal_alert(response.message || 'Operation failed', 'error');
                    }
                }).fail(function() {
                    showAjaxModal_alert('An error occurred', 'error');
                });
            },
            'Block',
            'danger'
        );
    }

    function account_unblock(student_id) {
        var class_id = '<?php echo $class_id; ?>';
        showConfirmModal(
            'Unblock Student Account',
            'Are you sure you want to unblock this student account?',
            function() {
                showAjaxModal_alert('Unblocking account...', 'loading');
                $.ajax({
                    url: '<?php echo site_url('admin/student/unblock/');?>' + student_id + '/' + class_id,
                    type: 'GET',
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message || 'Student account unblocked successfully', 'success');
                        location.reload();
                    } else {
                        showAjaxModal_alert(response.message || 'Operation failed', 'error');
                    }
                }).fail(function() {
                    showAjaxModal_alert('An error occurred', 'error');
                });
            },
            'Unblock',
            'success'
        );
    }

    function account_mute(student_id) {
        var class_id = '<?php echo $class_id; ?>';
        showConfirmModal(
            'Mute Student Account',
            'Are you sure you want to mute this student account?',
            function() {
                showAjaxModal_alert('Muting account...', 'loading');
                $.ajax({
                    url: '<?php echo site_url('admin/student/mute/');?>' + student_id + '/' + class_id,
                    type: 'GET',
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message || 'Student account muted successfully', 'success');
                        location.reload();
                    } else {
                        showAjaxModal_alert(response.message || 'Operation failed', 'error');
                    }
                }).fail(function() {
                    showAjaxModal_alert('An error occurred', 'error');
                });
            },
            'Mute',
            'danger'
        );
    }

    function account_unmute(student_id) {
        var class_id = '<?php echo $class_id; ?>';
        showConfirmModal(
            'Unmute Student Account',
            'Are you sure you want to unmute this student account?',
            function() {
                showAjaxModal_alert('Unmuting account...', 'loading');
                $.ajax({
                    url: '<?php echo site_url('admin/student/unmute/');?>' + student_id + '/' + class_id,
                    type: 'GET',
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message || 'Student account unmuted successfully', 'success');
                        location.reload();
                    } else {
                        showAjaxModal_alert(response.message || 'Operation failed', 'error');
                    }
                }).fail(function() {
                    showAjaxModal_alert('An error occurred', 'error');
                });
            },
            'Unmute',
            'success'
        );
    }

     function check_sms_status() {
        var active_sms_service = '<?php echo $active_sms_service; ?>';
        if(active_sms_service == '' || active_sms_service == 'disabled' || active_sms_service == null) {
            alert('No active SMS service found. Please go to System Settings and activate SMS service before sending SMS');
            //toastr.error('No active SMS service found. Please go to System Settings and activate SMS service and try again');
            $('.pt_link').removeAttr('href');
            $('.pt_link').attr({href: '#'});
            return false;

        }
    }

    function invoice_pay_modal(student_id, date = '', term = '') {

     /** invoice_code = invoice_code.toString();
      let invoice_original_len = '<?php echo isset($inv_number_len) ? $inv_number_len : 0; ?>';
      let current_invoice_len = invoice_code.length;

      if(invoice_code.substring(0, 1) == '_') {
          invoice_code = invoice_code.substring(1);
      } else {
          invoice_code = invoice_code;
      } **/
      if(date != '' && term == '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date, 'take_payment');
      } else if(date != '' && term != '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date + '/' + term, 'take_payment');
      }  else {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id, 'take_payment');
      }
  }

    function bulk_invoice_view_modal(student_id) {

        showAjaxModal('<?php echo site_url('modal/popup/modal_view_bulk_invoice/'); ?>' + student_id, 'take_payment');

    }

    // Cashier student view modal
    function showCashierStudentView(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_cashier_student_view/'); ?>' + student_id);
    }

    function showStudentBillReport(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_student_bill_report/'); ?>' + student_id, 'large');
    }
</script>

<!-- Floating Status Popup Menu -->
<div id="statusPopupMenu" class="action-popup-menu">
    <button id="statusBlockBtn" class="action-popup-item danger">
        <i class="glyphicon glyphicon-lock"></i> Block
    </button>
    <button id="statusUnblockBtn" class="action-popup-item success">
        <i class="fa fa-unlock"></i> Unblock
    </button>
    <div class="action-popup-divider"></div>
    <button id="statusMuteBtn" class="action-popup-item warning">
        <i class="glyphicon glyphicon-remove"></i> Mute
    </button>
    <button id="statusUnmuteBtn" class="action-popup-item success">
        <i class="glyphicon glyphicon-ok"></i> Unmute
    </button>
</div>

<!-- Floating Action Popup Menu -->
<div id="actionPopupMenu" class="action-popup-menu">
    <button id="actionPaymentBtn" class="action-popup-item success">
        <i class="entypo-credit-card"></i> Take Payment
    </button>
    <div class="action-popup-divider"></div>
    <button id="actionMarksheetBtn" class="action-popup-item primary">
        <i class="entypo-chart-bar"></i> Mark Sheet
    </button>
    <div class="action-popup-divider"></div>
    <button id="actionSmsBtn" class="action-popup-item success">
        <i class="glyphicon glyphicon-envelope"></i> Send SMS
    </button>
    <div class="action-popup-divider"></div>
    <button id="actionProfileBtn" class="action-popup-item info">
        <i class="entypo-user"></i> Profile
    </button>
    <button id="actionEditBtn" class="action-popup-item primary">
        <i class="entypo-pencil"></i> Edit
    </button>
    <button id="actionIdBtn" class="action-popup-item purple">
        <i class="entypo-vcard"></i> Generate ID
    </button>
    <div class="action-popup-divider"></div>
    <button id="actionDeleteBtn" class="action-popup-item danger">
        <i class="entypo-trash"></i> Delete
    </button>
</div>

<script type="text/javascript">
    // ============================================
    // POPUP MENU FUNCTIONALITY
    // ============================================
    
    var currentStudentId = null;
    var currentClassId = null;
    var currentActionData = null;
    var $statusPopupMenu = null;
    var $actionPopupMenu = null;
    
    $(function() {
        $statusPopupMenu = $('#statusPopupMenu');
        $actionPopupMenu = $('#actionPopupMenu');
        initPopupMenus();
    });
    
    function initPopupMenus() {
        // Close popup when clicking outside
        $(document).off('click.studentPopup').on('click.studentPopup', function(e) {
            if (!$(e.target).closest('.action-popup-btn, .action-popup-menu').length) {
                closeAllPopupMenus();
            }
        });
    }
    
    function closeAllPopupMenus() {
        if ($statusPopupMenu) $statusPopupMenu.removeClass('show');
        if ($actionPopupMenu) $actionPopupMenu.removeClass('show');
        $('.action-popup-btn').removeClass('active');
    }
    
    // ============================================
    // STATUS POPUP MENU
    // ============================================
    
    function toggleStatusMenu(btn, event, studentId) {
        event.stopPropagation();
        event.preventDefault();
        
        currentStudentId = studentId;
        
        // Ensure we have a valid DOM element
        var btnElement = btn instanceof jQuery ? btn[0] : btn;
        
        // If menu is already open, close it
        if ($statusPopupMenu && $statusPopupMenu.hasClass('show')) {
            closeAllPopupMenus();
            return;
        }
        
        closeAllPopupMenus();
        
        // Force menu to be rendered but hidden to get proper dimensions
        $statusPopupMenu.css({
            left: '-9999px',
            top: '-9999px'
        }).addClass('show');
        
        // Get actual menu dimensions
        var menuWidth = $statusPopupMenu.outerWidth();
        var menuHeight = $statusPopupMenu.outerHeight();
        
        // Hide again to reposition
        $statusPopupMenu.removeClass('show');
        
        // Position the menu - getBoundingClientRect gives viewport-relative coordinates
        // which is correct for position: fixed elements
        var btnRect = btnElement.getBoundingClientRect();
        
        var left = btnRect.left;
        var top = btnRect.bottom + 5;
        
        // Adjust if menu would go off right edge
        if (left + menuWidth > window.innerWidth) {
            left = window.innerWidth - menuWidth - 10;
        }
        
        // Adjust if menu would go off bottom edge
        if (top + menuHeight > window.innerHeight) {
            top = btnRect.top - menuHeight - 5;
        }
        
        // Ensure coordinates are within viewport
        left = Math.max(10, left);
        top = Math.max(10, top);
        
        $statusPopupMenu.css({
            left: left + 'px',
            top: top + 'px'
        });
        
        // Show menu
        $statusPopupMenu.addClass('show');
        $(btnElement).addClass('active');
    }
    
    // Status menu action handlers
    $(document).ready(function() {
        $('#statusBlockBtn').on('click', function() {
            if (currentStudentId) account_block(currentStudentId);
            closeAllPopupMenus();
        });
        
        $('#statusUnblockBtn').on('click', function() {
            if (currentStudentId) account_unblock(currentStudentId);
            closeAllPopupMenus();
        });
        
        $('#statusMuteBtn').on('click', function() {
            if (currentStudentId) account_mute(currentStudentId);
            closeAllPopupMenus();
        });
        
        $('#statusUnmuteBtn').on('click', function() {
            if (currentStudentId) account_unmute(currentStudentId);
            closeAllPopupMenus();
        });
    });
    
    // ============================================
    // ACTION POPUP MENU
    // ============================================
    
    function toggleActionMenu(btn, event, studentId) {
        event.stopPropagation();
        event.preventDefault();
        
        // Ensure we have a valid DOM element
        var btnElement = btn instanceof jQuery ? btn[0] : btn;
        var $btn = $(btnElement);
        var $row = $btn.closest('tr');
        var $dataSpan = $row.find('.action-data');
        
        currentStudentId = $dataSpan.data('student-id');
        currentClassId = $dataSpan.data('class-id');
        currentActionData = {
            marksheetUrl: $dataSpan.data('marksheet-url'),
            smsUrl: $dataSpan.data('sms-url'),
            profileUrl: $dataSpan.data('profile-url'),
            editUrl: $dataSpan.data('edit-url'),
            idUrl: $dataSpan.data('id-url'),
            hasInvoice: $dataSpan.data('has-invoice')
        };
        
        // Show/hide payment button based on invoice status
        if (currentActionData.hasInvoice == '1') {
            $('#actionPaymentBtn').show();
        } else {
            $('#actionPaymentBtn').hide();
        }
        
        // If menu is already open, close it
        if ($actionPopupMenu && $actionPopupMenu.hasClass('show')) {
            closeAllPopupMenus();
            return;
        }
        
        closeAllPopupMenus();
        
        // Force menu to be rendered but hidden to get proper dimensions
        $actionPopupMenu.css({
            left: '-9999px',
            top: '-9999px'
        }).addClass('show');
        
        // Get actual menu dimensions
        var menuWidth = $actionPopupMenu.outerWidth();
        var menuHeight = $actionPopupMenu.outerHeight();
        
        // Hide again to reposition
        $actionPopupMenu.removeClass('show');
        
        // Position the menu - getBoundingClientRect gives viewport-relative coordinates
        // which is correct for position: fixed elements
        var btnRect = btnElement.getBoundingClientRect();
        
        var left = btnRect.left;
        var top = btnRect.bottom + 5;
        
        // Adjust if menu would go off right edge
        if (left + menuWidth > window.innerWidth) {
            left = window.innerWidth - menuWidth - 10;
        }
        
        // Adjust if menu would go off bottom edge
        if (top + menuHeight > window.innerHeight) {
            top = btnRect.top - menuHeight - 5;
        }
        
        // Ensure coordinates are within viewport
        left = Math.max(10, left);
        top = Math.max(10, top);
        
        $actionPopupMenu.css({
            left: left + 'px',
            top: top + 'px'
        });
        
        // Show menu
        $actionPopupMenu.addClass('show');
        $btn.addClass('active');
    }
    
    // Action menu handlers
    $(document).ready(function() {
        $('#actionPaymentBtn').on('click', function() {
            if (currentStudentId) invoice_pay_modal(currentStudentId);
            closeAllPopupMenus();
        });
        
        $('#actionMarksheetBtn').on('click', function() {
            if (currentActionData && currentActionData.marksheetUrl) {
                navigation(currentActionData.marksheetUrl);
            }
            closeAllPopupMenus();
        });
        
        $('#actionSmsBtn').on('click', function() {
            if (currentActionData && currentActionData.smsUrl) {
                check_sms_status();
                navigation(currentActionData.smsUrl);
            }
            closeAllPopupMenus();
        });
        
        $('#actionProfileBtn').on('click', function() {
            if (currentActionData && currentActionData.profileUrl) {
                navigation(currentActionData.profileUrl);
            }
            closeAllPopupMenus();
        });
        
        $('#actionEditBtn').on('click', function() {
            if (currentActionData && currentActionData.editUrl) {
                showAjaxModal(currentActionData.editUrl);
            }
            closeAllPopupMenus();
        });
        
        $('#actionIdBtn').on('click', function() {
            if (currentActionData && currentActionData.idUrl) {
                showAjaxModal(currentActionData.idUrl);
            }
            closeAllPopupMenus();
        });
        
        $('#actionDeleteBtn').on('click', function() {
            if (currentStudentId && currentClassId) {
                deleteStudent(currentStudentId, currentClassId);
            }
            closeAllPopupMenus();
        });
    });

</script>
