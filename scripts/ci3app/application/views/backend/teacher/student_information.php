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
    }

}

    $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;


?>

<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo get_phrase('student_information'); ?></h2>
            <p class="text-gray-600"><?php echo $this->db->get_where('class', array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;?> - <?php echo get_phrase('year');?>: <?php echo $running_year;?></p>
        </div>
        <div class="flex gap-3">
            <a href="#panel_exam" data-toggle="collapse" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-500 to-pink-500 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                <i class="entypo-chart-line mr-2"></i>
                <?php echo get_phrase('bulk_marksheets');?>
            </a>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-md-12">

        <ul class="nav nav-tabs bordered p-5">
            <li class="active">
                <a href="#home" data-toggle="tab">
                    <span class="visible-xs"><i class="entypo-users"></i></span>
                    <span class="hidden-xs"><?php echo 'All Students';?></span>
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
            <li class="flex flex-col col-md-12 col-lg-12 col-sm-12 col-xs-12">
                <div class="grid grid-cols-1 gap-4 p-5 shadow-lg border-t-2 border-sky-500 mt-5 rounded-t-lg">
                    <div class="col-span-3" style="justify-self: flex-start;">
                            <a href="#panel_exam" data-toggle="collapse" id="choose_exam"
                            class="btn rounded-lg btn-danger pull-left h-16 text-xl font-bold content-center" target="_blank">
                                <span class="hidden-xs hidden-sm"><i class="entypo-chart-line"></i>
                                <?php echo 'Bulk Marksheets';?></span>

                                <span class="visible-xs visible-sm"><i class="entypo-chart-line"></i>
                                <?php echo 'Bulk Marksheets';?></span>
                            </a>
                            
                        </div>

                        <!-- <div class="col-md-3 col-lg-3 col-sm-3 col-xs-6">
                            <a href="<?php echo site_url('admin/bulk_cummulative_reports/'.$class_id); ?>"
                            class="btn rounded-lg btn-success pull-right h-16 text-xl font-bold content-center" target="_blank">
                                <span class="hidden-xs hidden-sm"><i class="entypo-chart-bar"></i>
                                <?php echo 'Generate Cummulative Reports';?></span>

                                <span class="visible-xs visible-sm"><i class="entypo-chart-bar"></i>
                                <?php echo 'Generate Cummulative Reports';?></span>
                            </a>
                        </div>
                    <div class="col-md-2 col-lg-2 col-sm-2 col-xs-6">
                        <div class="col-md-3 col-sm-3 col-xs-4">
                            <?php
                                if($class_name == 'JHSS') {

                                    ?>
                                        <a href="<?php echo site_url('admin/student_information_print/'.$class_id.'/'.$running_year.'/'.$running_sem);?>"
                            class="btn rounded-lg btn-info h-16 text-xl font-bold content-center" target="_blank">
                                    <?php
                                } else {
                                    ?>
                                        <a href="<?php echo site_url('admin/student_information_print/'.$class_id.'/'.$running_year.'/'.$running_term);?>"
                            class="btn rounded-lg btn-info h-16 text-xl font-bold content-center" target="_blank">
                                    <?php
                                }
                            ?>
                            
                                <span class="hidden-xs hidden-sm"><i class="entypo-print"></i>
                                <?php echo 'Print Info';?></span>

                                <span class="visible-xs visible-sm"><i class="entypo-print"></i>
                                <?php echo 'All';?></span>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-2 col-lg-2 col-sm-2 col-xs-6">
                        <a href="#" onclick="navigation('<?php echo site_url('admin/student_add');?>')"
                        class="btn rounded-lg btn-primary h-16 text-xl font-bold content-center">
                            <span class="hidden-xs hidden-sm"><i class="entypo-plus-circled"></i>
                            <?php echo 'Admit Student';?></span>

                            <span class="visible-xs visible-sm"><i class="entypo-plus-circled"></i>
                            <?php echo 'Admit';?></span>
                        </a>
                    </div> -->
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

                        
                            <div class="flex gap-5">
                                <div class="flex gap-5 justify-around items-center w-full max-w-full">
                                    <label for="to_class_id" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white w-1/5 max-w-1/5 uppercase">Select Exam Type</label>
                                    <select id="exam_id" name="exam_id" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-2/5 max-w-2/5 h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                        <option value="">CHOOSE EXAMINATION</option>
                                        <?php 
                                        $this->db->select('exam_id');
                                        $this->db->distinct();
                                        $exams_query = $this->db->get_where('mark', array('class_id' => $class_id));
                                        

                                        if($class_name == 'JHSS') {
                                            if($exams_query->num_rows() > 0) {
                                            $exams = $exams_query->result_array();
                                                foreach($exams as $e_row):

                                                    $ex_q = $this->db->get_where('exam', array('exam_id' => $e_row['exam_id']))->row();


                                                    $exam_name = $ex_q->name;
                                                    $ex_year = $ex_q->year;
                                                    $ex_sem = $ex_q->sem;
                                                 ?>
                                            
                                            <option value="<?php echo $e_row['exam_id']; ?>"><?php echo $exam_name. ', Year: '. explode('-', $ex_year)[1].' | Semester: '.$ex_sem; ?></option>

                                            <?php endforeach; 
                                            } else {
                                                echo '<option>No exam found</option>';
                                            }

                                        } else {

                                            if($exams_query->num_rows() > 0) {
                                                $exams = $exams_query->result_array();
                                                foreach($exams as $e_row): 

                                                    $ex_q = $this->db->get_where('exam', array('exam_id' => $e_row['exam_id']))->row();


                                                    $exam_name = $ex_q->name;
                                                    $ex_year = $ex_q->year;
                                                    $ex_term = $ex_q->term;
                                                    ?>
                                            
                                            <option value="<?php echo $e_row['exam_id']; ?>"><?php echo $exam_name. ', Year: '. explode('-', $ex_year)[1].' | Term: '.$ex_term; ?></option>
                                            <?php endforeach; 
                                            } else {
                                                echo '<option>No exam found</option>';
                                            }

                                        }
                                            
                                            ?>
                                    </select>
                                    <input type="submit" class="btn rounded-lg btn-info h-20 text-2xl font-bold uppercase" id="c_print" name="print_b" value="Click here to print">
                                </div>
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
        </ul>

        <div class="tab-content">
            <div style="display: <?= $display; ?>; margin-top: 10px" class="alert alert-<?= $alert_type; ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
               <strong> <?php echo $feedback; ?></strong>
            </div>
            <hr>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg p-8 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white bg-opacity-30 rounded-lg p-4">
                                <i class="fas fa-male text-3xl"></i>
                            </div>
                        </div>
                        <div class="text-4xl font-bold mb-1" id="total_males">...</div>
                        <div class="text-base font-medium opacity-95"><?php echo get_phrase('total_males');?></div>
                    </div>

                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl shadow-lg p-8 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white bg-opacity-30 rounded-lg p-4">
                                <i class="fas fa-female text-3xl"></i>
                            </div>
                        </div>
                        <div class="text-4xl font-bold mb-1" id="total_females">...</div>
                        <div class="text-base font-medium opacity-95"><?php echo get_phrase('total_females');?></div>
                    </div>

                    <div class="bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl shadow-lg p-8 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white bg-opacity-30 rounded-lg p-4">
                                <i class="fas fa-question text-3xl"></i>
                            </div>
                        </div>
                        <div class="text-4xl font-bold mb-1" id="total_unset_gender">...</div>
                        <div class="text-base font-medium opacity-95"><?php echo get_phrase('unset_gender');?></div>
                    </div>

                    <?php if($boarding_system == 'yes'): ?>
                    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg p-8 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white bg-opacity-30 rounded-lg p-4">
                                <i class="fas fa-bed text-3xl"></i>
                            </div>
                        </div>
                        <div class="text-4xl font-bold mb-1"><?=number_format($this->boarding_model->classBoardersCount($class_id), 0, '.', ',');?></div>
                        <div class="text-base font-medium opacity-95"><?php echo get_phrase('total_boarders');?></div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl shadow-lg p-8 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white bg-opacity-30 rounded-lg p-4">
                                <i class="fas fa-sun text-3xl"></i>
                            </div>
                        </div>
                        <div class="text-4xl font-bold mb-1"><?=number_format($this->boarding_model->classDayStudentsCount($class_id), 0, '.', ',');?></div>
                        <div class="text-base font-medium opacity-95"><?php echo get_phrase('day_students');?></div>
                    </div>
                    <?php endif; ?>
                </div>
            <hr>

            <div class="tab-pane active" id="home">
                <?php echo form_open(site_url('admin/bulk_students_delete/'. $class_id), array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'checkboxes_form', 'enctype' => 'multipart/form-data'));?>

                <table class="w-full text-xl text-left text-gray-500 dark:text-gray-400 datatable" id="">
                  <thead class="text-xl text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                      <tr>
                          <th scope="col" class="px-4 py-3">ID No</th>
                          <th scope="col" class="px-4 py-3">Name</th>
                          <th scope="col" class="px-4 py-3">Gender</th>
                          <!-- <th scope="col" class="px-4 py-3">Address</th> -->
                          <th scope="col" class="px-4 py-3">Residence Type</th>
<!--                           <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Auth Key</th>
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Account Status</th> -->
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Option</th>
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
                                
                            if($student_query->num_rows() > 0):
                                foreach($students as $row):?>

                                <?php 
                                    $student_info = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row();

                                    $students_acc_st = $student_info->block_limit;

                                    $students_mute = $student_info->mute;

                                    $enrollmentRow = $this->crud_model->getStudentCurrentEnrollmentStatusRow($row['student_id']);

                                    $gender = $student_info->sex;

                                    $sGender = strtolower($gender);

                                    if($sGender == 'female') {

                                        $class_female++;

                                    } else if($sGender == 'male') {

                                        $class_male++;

                                    } else {
                                        
                                        $class_unknown++;
                                    }

                                    ?>
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
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
                               <!-- <a href="mailto:<?php  //echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->email;?>" target="_blank"> <?php //echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->email;?></a> -->
                            </td> 
                            <!-- <td class="px-4 py-3 text-center" style="font-weight: bolder; letter-spacing: 3px;" width="100">
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
                            </td> -->
                            <td class="px-4 py-3 text-center" width="100">

                                <div class="rounded-lg btn-group">
                                    <?=get_action_button();?>
                                    <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                                        <!-- STUDENT MARKSHEET LINK  -->
                                        <?php 
                                            //choosing between creche and the general classes
                                            $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                                            $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
                                            if($class_name == 'CRECHE') { ?>

                                                <li>
                                                <a href="#" style='color: #040f10;' onclick="navigation('<?php echo site_url('admin/student_marksheet_creche/'.$row['student_id']);?>')">
                                                    <i class="entypo-chart-bar"></i>
                                                        Mark Sheet
                                                    </a>
                                                </li>
                                                <li class="divider"></li>
                                            <?php
                                            } else {
                                                ?>
                                                    <li>
                                                        <a href="#" style='color: #040f10;' onclick="navigation('<?php echo site_url('admin/student_marksheet/'.$row['student_id']);?>')">
                                                            <i class="entypo-chart-bar"></i>
                                                                Mark Sheet
                                                        </a>
                                                    </li>
                                                    <li class="divider"></li>
                                                <?php
                                            }
                                        ?>
                                        
                                       

                                        <!-- STUDENT PROFILE LINK -->
                                        <li>
                                            <a href="#" onclick="navigation('<?php echo site_url('admin/student_profile/'.$row['student_id']);?>')" style='color: #0029ff;'>
                                                <i class="entypo-user"></i>
                                                    Profile
                                                </a>
                                        </li>
                                        <li class="divider"></li>

                                        
                                    </ul>
                                </div>

                            </td>
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
                                <div class="flex gap-4 mt-12 p-5">
                                   <a href="#" id="move_student" class="btn rounded-lg btn-info h-14 text-xl font-bold text-gray-600">Move Students</a>

                                   <input type="submit" name="submit_delete" id="submit_delete" class="btn rounded-lg btn-danger h-14 text-xl font-bold text-gray-600" value="Delete Selected Students">
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



</script>
