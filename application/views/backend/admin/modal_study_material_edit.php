<?php
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

$data = array(
    'CRECHE', 'NURSERY', 'KG'
);
$this->db->where_not_in('name', $data);
$class_info = $this->db->get('class')->result_array();
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

                <?php echo form_open(site_url('admin/study_material/update/'.$row['document_id']), array('id' => 'edit_form', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4 mb-5">
                    <div class="">
                        <label for="field-1" class="control-label"><?php echo get_phrase('date'); ?></label>

                        <div class="">
                            <input type="text" name="timestamp" class="w-full px-4 py-3 bg-gray-50 border datepicker border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" data-format="D, dd MM yyyy"
                                   placeholder="<?php echo get_phrase('select_date'); ?>" value="<?php echo date("d M, Y", $row['timestamp']); ?>" required>
                        </div>
                    </div>

                    <div class="">
                        <label for="field-1" class="control-label"><?php echo get_phrase('title'); ?></label>

                        <div class="">
                            <input type="text" name="title" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="field-1" value="<?php echo $row['title']; ?>" required>
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
                        <input id="datepicker-range-start-edit" date-rangepicker name="start" type="text" class="w-full px-4 py-3 datepicker bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date start" value="<?php echo date("d M, Y", $row['start_date']); ?>">
                    </div>
                      <span class="mx-4 text-gray-700">To</span>
                      <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                             <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                            </svg>
                        </div>
                        <input id="datepicker-range-end-edit" date-rangepicker name="end" type="text" class="w-full px-4 py-3 bg-gray-50 border datepicker border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date end" value="<?php echo date("d M, Y", $row['end_date']); ?>">
                    </div>
                </div>

                <div class=" px-4 mb-5">
                    <label for="field-ta" class="control-label"><?php echo get_phrase('description'); ?></label>

                    <div class="">
                        <textarea name="description" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="field-ta"><?php echo $row['description']; ?></textarea>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4 mb-5">
                    <div class="">
                        <label for="field-ta" class="control-label"><?php echo get_phrase('class'); ?></label>

                        <div class="">
                            <select name="class_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 select2" id="class_id_edit" onchange="return get_class_subject_edit(this.value)" required="">
                                <option value=""><?php echo get_phrase('select_class'); ?></option>
                                <?php foreach ($class_info as $row2) {
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $row2['class_id']))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $row2['name'], 'name_numeric' => $row2['name_numeric']))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = ' - ' . $section_name;
                                    }
                                ?>
                                    <option value="<?php echo $row2['class_id']; ?>" <?php if ($row['class_id'] == $row2['class_id']) echo 'selected'; ?>>
                                        <?php echo $row2['name'].' '.$row2['name_numeric'].$sec_name ; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="">
                        <label for="field-2" class="control-label"><?php echo get_phrase('subject'); ?></label>
                        <div class="">
                            <select name="subject_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" id="subject_selector_holder_edit" required="required">
                               <?php
                               $subject = $this->db->get_where('subject',array('class_id'=>$row['class_id'], 'year' => $running_year, 'term' => $running_term))->result_array();
                               foreach ($subject as $row2){                           
                               ?>
                                <option value="<?php echo $row2['subject_id']; ?>" <?php if ($row['subject_id'] == $row2['subject_id']) echo 'selected'; ?>>
                                        <?php echo $row2['name']; ?>
                                    </option>
                               <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10  py-4 mb-5">
                    <div class="">
                        <label class="control-label"><?php echo get_phrase('file'); ?> <small class="text-gray-500">(<?php echo get_phrase('optional'); ?>)</small></label>

                        <div class="">
                            <input type="file" name="file_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 file2 inline btn btn-primary" data-label="<i class='glyphicon glyphicon-file'></i> Browse" />
                            <small class="text-gray-500"><?php echo get_phrase('leave_empty_to_keep_current_file'); ?></small>
                        </div>
                    </div>

                    <div class="">
                        <label for="field-ta" class="control-label"><?php echo get_phrase('file_type'); ?></label>

                        <div class="">
                            <select name="file_type" class="form-control select2">
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

    function get_class_subject_edit(class_id) {
        if (class_id !== '') {
            $.ajax({
                url: '<?php echo site_url('admin/get_class_subject/'); ?>' + class_id,
                success: function (response)
                {
                    $('#subject_selector_holder_edit').html(response);
                }
            });
        }
    }

    jQuery(document).ready(function($) {
        // Initialize datepickers
        $('.datepicker').datepicker({
            format: 'dd M, yyyy',
            autoclose: true,
            todayHighlight: true
        });
        
        // AJAX form submission
        $('#edit_form').submit(function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    // Close modal
                    $('.close').click();
                    // Show success message
                    toastr.success('Study material updated successfully!');
                    // Reload table
                    if(typeof applyFilters === 'function') {
                        applyFilters();
                    } else if(typeof loadTable === 'function') {
                        loadTable();
                    }
                },
                error: function() {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });
    });

</script>
