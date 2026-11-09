<?php
    $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
    $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

$single_study_material_info = $this->db->get_where('document', array('document_id' => $param2))->result_array();
foreach ($single_study_material_info as $row) {
?>
<div class="row">
    <div class="col-md-12">

        <div class="panel panel-primary" data-collapsed="0">

            <div class="panel-heading">
                <div class="panel-title">
                    <?php echo get_phrase('edit_study_material'); ?>
                </div>
            </div>

            <div class="panel-body ps-10">

                <?php echo form_open(site_url('teacher/study_material/update/'.$row['document_id']), array('id' => 'study_material_edit_form', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4 mb-5">
                    <div class="">
                        <label for="field-1" class="control-label"><?php echo get_phrase('date'); ?></label>

                        <div class="">
                            <input type="text" name="timestamp" class="w-full px-4 py-3 bg-gray-50 border datepicker border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" data-format="D, dd MM yyyy"
                                   placeholder="<?php echo get_phrase('select_date'); ?>" value="<?php echo date("d M, Y", $row['timestamp']); ?>" required>
                        </div>
                    </div>

                    <div class="">
                        <label for="field-1" class="control-label"><?php echo get_phrase('title'); ?></label>

                        <div class="">
                            <input type="text" name="title" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="field-1" value="<?php echo $row['title']; ?>" required>
                        </div>
                    </div>
                </div>

                <div id="date-range-picker" date-rangepicker class="flex items-center md:ml-16  py-4 mb-5">
                    <span class="mx-4 text-gray-700">From</span>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                             <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                            </svg>
                        </div>
                        <input id="datepicker-range-start-edit" date-rangepicker name="start" type="text" class="w-full px-4 py-3 datepicker bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date start" value="<?php echo date("d M, Y", $row['start_date']); ?>">
                    </div>
                      <span class="mx-4 text-gray-700">To</span>
                      <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                             <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                            </svg>
                        </div>
                        <input id="datepicker-range-end-edit" date-rangepicker name="end" type="text" class="w-full px-4 py-3 bg-gray-50 border datepicker border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date end" value="<?php echo date("d M, Y", $row['end_date']); ?>">
                    </div>
                </div>

                <div class=" px-4 mb-5">
                    <label for="field-ta" class="control-label"><?php echo get_phrase('description'); ?></label>

                    <div class="">
                        <textarea name="description" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="field-ta"><?php echo $row['description']; ?></textarea>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4 mb-5">
                    <div class="">
                        <label for="field-ta" class="control-label"><?php echo get_phrase('class'); ?></label>

                        <div class="">
                            <select name="class_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="class_id_edit" onchange="return get_class_subject_edit(this.value)" required="">
                                <option value=""><?php echo get_phrase('select_class'); ?></option>
                                <?php 
                                // Get distinct classes where teacher teaches subjects
                                $this->db->distinct();
                                $this->db->select('class_id');
                                $this->db->from('subject');
                                $this->db->where('teacher_id', $this->session->userdata('teacher_id'));
                                $this->db->where('year', $running_year);
                                $this->db->where('term', $running_term);
                                $class_ids_result = $this->db->get()->result_array();
                                
                                foreach($class_ids_result as $class_data):
                                    $class_info = $this->db->get_where('class', array('class_id' => $class_data['class_id']))->row();
                                    if($class_info):
                                        // Get section name
                                        $section = $this->db->get_where('section', array('class_id' => $class_info->class_id))->row();
                                        $section_name = $section ? $section->name : '';
                                        
                                        // Check if class has multiple sections
                                        $class_has_more_sections = $this->db->get_where('class', array('name' => $class_info->name, 'name_numeric' => $class_info->name_numeric))->num_rows();
                                        $sec_display = ($class_has_more_sections > 1) ? ' - '.$section_name : '';
                                ?>
                                    <option value="<?php echo $class_info->class_id; ?>" <?php if ($row['class_id'] == $class_info->class_id) echo 'selected'; ?>>
                                        <?php echo $class_info->name.' '.$class_info->name_numeric.$sec_display; ?>
                                    </option>
                                <?php 
                                    endif;
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="">
                        <label for="field-2" class="control-label"><?php echo get_phrase('subject'); ?></label>
                        <div class="">
                            <select name="subject_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="subject_selector_holder_edit" required="required">
                               <?php
                               // Only show subjects that the teacher teaches in this class
                               $subject = $this->db->get_where('subject',array(
                                   'class_id'=>$row['class_id'], 
                                   'teacher_id' => $this->session->userdata('teacher_id'),
                                   'year' => $running_year,
                                   'term' => $running_term
                               ))->result_array();
                               
                               if(empty($subject)):
                               ?>
                                   <option value=""><?php echo get_phrase('no_subjects_found'); ?></option>
                               <?php
                               else:
                                   foreach ($subject as $row2):
                               ?>
                                    <option value="<?php echo $row2['subject_id']; ?>" <?php if ($row['subject_id'] == $row2['subject_id']) echo 'selected'; ?>>
                                        <?php echo $row2['name']; ?>
                                    </option>
                               <?php 
                                   endforeach;
                               endif;
                               ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10  py-4 mb-5">
                    <div class="">
                        <label class="control-label"><?php echo get_phrase('file'); ?> <small class="text-gray-500">(<?php echo get_phrase('optional'); ?>)</small></label>

                        <div class="">
                            <input type="file" name="file_name" class="form-control" accept=".doc,.docx,.pdf,.xls,.xlsx,.png,.jpg,.jpeg,.gif" />
                            <small class="text-gray-500"><?php echo get_phrase('leave_empty_to_keep_current_file'); ?></small>
                        </div>
                    </div>

                    <div class="">
                        <label for="field-ta" class="control-label"><?php echo get_phrase('file_type'); ?></label>

                        <div class="">
                            <select name="file_type" class="form-control">
                                <option value=""><?php echo get_phrase('select_file_type'); ?></option>
                                <option value="doc" <?php if($row['file_type'] == 'doc') echo 'selected'; ?>><?php echo get_phrase('docx'); ?>/Ms Word</option>
                                <option value="pdf" <?php if($row['file_type'] == 'pdf') echo 'selected'; ?>><?php echo get_phrase('pdf'); ?></option>
                                <option value="image" <?php if($row['file_type'] == 'image') echo 'selected'; ?>><?php echo get_phrase('image'); ?></option>
                                <option value="excel" <?php if($row['file_type'] == 'excel') echo 'selected'; ?>><?php echo get_phrase('excel'); ?></option>
                                <option value="other" <?php if($row['file_type'] == 'other') echo 'selected'; ?>><?php echo get_phrase('other'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center items-center">
                    <button type="submit" id="submit_edit" class="cursor-pointer px-4 py-3 rounded-lg bg-green-500 hover:bg-green-600 text-white text-2xl font-bold"><?php echo get_phrase('update'); ?></button>
                </div>
                </form>

            </div>

        </div>

    </div>
</div>
<?php } ?>

<script type="text/javascript">
// Initialize datepickers when modal is shown
$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'dd M, yyyy',
        autoclose: true,
        todayHighlight: true
    });
});

function get_class_subject_edit(class_id) {
    if (class_id !== '') {
        $.ajax({
            url: '<?php echo site_url('teacher/get_class_subject/'); ?>' + class_id,
            success: function (response) {
                $('#subject_selector_holder_edit').html(response);
            }
        });
    }
}

jQuery(document).ready(function($) {
    $('#study_material_edit_form').submit(function(e) {
        e.preventDefault();
        showAjaxModal_alert('Updating study material...', 'loading');
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success', false);
                // Close form modal after alert shows
                setTimeout(function() {
                    $('#modal_ajax').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }, 500);
                // Refresh page content via AJAX instead of full reload
                setTimeout(() => {
                    $.ajax({
                        url: '<?php echo site_url('teacher/study_material'); ?>',
                        type: 'GET',
                        success: function(html) {
                            // Extract the main content from the response
                            var newContent = $(html).find('.bg-white.rounded-xl.shadow-lg').html();
                            if(newContent) {
                                $('.bg-white.rounded-xl.shadow-lg').html(newContent);
                                // Reinitialize datatable
                                if($.fn.dataTable.isDataTable('#table-2')) {
                                    $('#table-2').DataTable().destroy();
                                }
                                $('#table-2').dataTable({
                                    "pageLength": 10,
                                    "order": [[0, "desc"]]
                                });
                            } else {
                                location.reload();
                            }
                        },
                        error: function() {
                            location.reload();
                        }
                    });
                }, 1500);
            } else {
                showAjaxModal_alert(data.message || 'Operation failed', 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred', 'error');
        });
    });
});
</script>
