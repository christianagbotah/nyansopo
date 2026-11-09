<div class="row">
    <div class="col-md-12">

        <div class="panel panel-primary" data-collapsed="0">

            <div class="panel-heading">
                <div class="panel-title">
                    <?php echo get_phrase('add_study_material'); ?>
                </div>
            </div>

            <div class="panel-body ps-10">

                <?php echo form_open(site_url('teacher/study_material/create'), array('id' => 'study_material_form', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4 mb-5">
                    <div class="">
                        <label for="field-1" class="control-label"><?php echo get_phrase('date'); ?></label>

                        <div class="">
                            <input type="text" name="timestamp" class="w-full px-4 py-3 bg-gray-50 border datepicker border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 datepicker" data-format="D, dd MM yyyy"
                                   placeholder="<?php echo get_phrase('select_date'); ?>" required>
                        </div>
                    </div>

                    <div class="">
                        <label for="field-1" class="control-label"><?php echo get_phrase('title'); ?></label>

                        <div class="">
                            <input type="text" name="title" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="field-1" required>
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
                        <input id="datepicker-range-start" date-rangepicker name="start" type="text" class="w-full px-4 py-3 datepicker bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date start">
                    </div>
                      <span class="mx-4 text-gray-700">To</span>
                      <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                             <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                            </svg>
                        </div>
                        <input id="datepicker-range-end" date-rangepicker name="end" type="text" class="w-full px-4 py-3 bg-gray-50 border datepicker border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date end">
                    </div>
                </div>

                <div class=" px-4 mb-5">
                    <label for="field-ta" class="control-label"><?php echo get_phrase('description'); ?></label>

                    <div class="">
                        <textarea name="description" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="field-ta"></textarea>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4 mb-5">
                    <div class="">
                        <label for="field-ta" class="control-label"><?php echo get_phrase('class'); ?></label>

                        <div class="">
                            <select name="class_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="class_id" onchange="return get_class_subject(this.value)" required="">
                                <option value=""><?php echo get_phrase('select_class'); ?></option>
                                <?php 
                                $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
                                $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
                                $teacher_id = $this->session->userdata('teacher_id');
                                
                                // Get distinct classes where teacher teaches subjects
                                $this->db->distinct();
                                $this->db->select('class_id');
                                $this->db->from('subject');
                                $this->db->where('teacher_id', $teacher_id);
                                $this->db->where('year', $running_year);
                                $this->db->where('term', $running_term);
                                $class_ids = $this->db->get()->result_array();
                                
                                foreach($class_ids as $class_data):
                                    $class = $this->db->get_where('class', array('class_id' => $class_data['class_id']))->row();
                                    if($class):
                                        // Get section name
                                        $section = $this->db->get_where('section', array('class_id' => $class->class_id))->row();
                                        $section_name = $section ? $section->name : '';
                                        
                                        // Check if class has multiple sections
                                        $class_has_more_sections = $this->db->get_where('class', array('name' => $class->name, 'name_numeric' => $class->name_numeric))->num_rows();
                                        $sec_display = ($class_has_more_sections > 1) ? ' - '.$section_name : '';
                                ?>
                                    <option value="<?php echo $class->class_id; ?>">
                                        <?php echo $class->name.' '.$class->name_numeric.$sec_display; ?>
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
                            <select name="subject_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="subject_selector_holder" required="required">
                                <option value="" disabled="true"><?php echo get_phrase('select_class_first'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10  py-4 mb-5">
                    <div class="">
                        <label class="control-label"><?php echo get_phrase('file'); ?></label>

                        <div class="">
                            <input type="file" name="file_name" class="form-control" accept=".doc,.docx,.pdf,.xls,.xlsx,.png,.jpg,.jpeg,.gif" required />
                        </div>
                    </div>

                    <div class="">
                        <label for="field-ta" class="control-label"><?php echo get_phrase('file_type'); ?></label>

                        <div class="">
                            <select name="file_type" class="form-control">
                                <option value=""><?php echo get_phrase('select_file_type'); ?></option>
                                <option value="doc" selected><?php echo get_phrase('docx'); ?>/Ms Word</option>
                                <option value="pdf"><?php echo get_phrase('pdf'); ?></option>
                                <option value="image"><?php echo get_phrase('image'); ?></option>
                                <option value="excel"><?php echo get_phrase('excel'); ?></option>
                                <option value="other"><?php echo get_phrase('other'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center items-center">
                    <button type="submit" id = "submit" class="cursor-pointer px-4 py-3 rounded-lg bg-blue-500 text-white text-2xl font-bold"><?php echo get_phrase('Submit_Lesson_note'); ?></button>
                </div>
                </form>

            </div>

        </div>

    </div>
</div>

<script type="text/javascript">
// Initialize datepickers when modal is shown
$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'dd M, yyyy',
        autoclose: true,
        todayHighlight: true
    });
});

function get_class_subject(class_id) {
    if (class_id !== '') {
        $.ajax({
            url: '<?php echo site_url('teacher/get_class_subject/'); ?>' + class_id,
            success: function (response) {
                $('#subject_selector_holder').html(response);
            }
        });
    }
}

var class_id = '';
jQuery(document).ready(function($) {
    $("#submit").attr('disabled', 'disabled');
    
    $('#study_material_form').submit(function(e) {
        e.preventDefault();
        showAjaxModal_alert('Creating study material...', 'loading');
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

function check_validation(){
    if(class_id !== ''){
        $('#submit').removeAttr('disabled');
    } else {
        $("#submit").attr('disabled', 'disabled');
    }
}

$('#class_id').change(function(){
    class_id = $('#class_id').val();
    check_validation();
});
</script>
