<?php 
    $raw_score = $this->db->get_where('settings', array('type' => 'raw_score'))->row()->description;
?>

<style type="text/css">
    /* Base Typography */
    body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    .validate-has-error { color: red; }
    label { font-size: 1.125rem !important; font-weight: 700 !important; color: #1f2937 !important; }
    input, select, textarea { font-size: 1.125rem !important; font-weight: 500 !important; color: #111827 !important; }
    h3 { font-size: 1.5rem !important; font-weight: 800 !important; color: #111827 !important; }
    h4 { font-size: 1.25rem !important; font-weight: 800 !important; color: #111827 !important; }
    p { font-size: 1.0625rem !important; line-height: 1.7 !important; font-weight: 500 !important; color: #374151 !important; }
    
    /* Select2 Fix */
    .select2-container { width: 100% !important; }
    .select2-container .select2-selection--single { height: 48px !important; padding: 10px 14px !important; font-size: 1.125rem !important; font-weight: 600 !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 28px !important; font-size: 1.125rem !important; font-weight: 600 !important; color: #111827 !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px !important; }
    
    /* Uniform Button Size */
    .btn-uniform { min-height: 52px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; font-size: 1.125rem !important; font-weight: 700 !important; letter-spacing: 0.025em !important; }
    
    /* Fix label button height and text color */
    label.btn-uniform { height: 52px !important; padding: 0 1.5rem !important; box-sizing: border-box !important; color: white !important; }
    
    /* Table Readability */
    .datatable tbody td { font-size: 1.125rem !important; padding: 1rem 1.5rem !important; font-weight: 600 !important; color: #111827 !important; }
    .datatable thead th { font-size: 1.0625rem !important; padding: 1rem 1.5rem !important; letter-spacing: 0.05em !important; font-weight: 700 !important; color: #374151 !important; }
    
    /* Enhanced Text Contrast */
    .text-gray-700 { color: #1f2937 !important; font-weight: 600 !important; }
    .text-gray-800 { color: #111827 !important; font-weight: 700 !important; }
    .text-gray-900 { color: #000000 !important; font-weight: 700 !important; }
</style>

<div class="max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-3xl font-bold text-white flex items-center gap-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            Subject Management
        </h2>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <ul class="flex border-b border-gray-200" role="tablist">
            <li class="flex-1">
                <a href="#list" data-toggle="tab" class="flex items-center justify-center gap-2 px-6 py-4 text-lg font-semibold text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition active">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    Subject List
                </a>
            </li>
            <li class="flex-1">
                <a href="#add" data-toggle="tab" class="flex items-center justify-center gap-2 px-6 py-4 text-lg font-semibold text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Subject
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- LIST TAB -->
            <div class="tab-pane active" id="list">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="table table-bordered datatable w-full" id="table_export">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Options</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($subjects as $row):
                                    $class = $this->db->get_where('class', array('class_id' => $row['class_id']))->result_array();
                                    foreach ($class as $c):
                                        $section_name = $this->db->get_where('section', array('class_id' => $c['class_id']))->row()->name;
                                        $class_has_more_sections = $this->db->get_where('class', array('name' => $c['name'], 'name_numeric' => $c['name_numeric']))->num_rows();
                                        $sec_name = $class_has_more_sections > 1 ? $section_name : '';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $this->crud_model->get_type_name_by_id('class',$row['class_id']).' '.$c['name_numeric'].$sec_name;?></td>
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold"><?php echo $row['name'];?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?php echo site_url($account_type.'/teacher_details/'.$row['teacher_id']); ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <?php echo $this->crud_model->get_type_name_by_id('teacher',$row['teacher_id']);?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="btn-group">
                                            <?=get_action_button();?>
                                            <ul class="dropdown-menu dropdown-default pull-right" role="menu">
                                                <li>
                                                    <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_edit_subject/'.$row['subject_id'].'/'.$class_name);?>');" class="text-green-600">
                                                        <i class="entypo-pencil"></i> Edit
                                                    </a>
                                                </li>
                                                <li class="divider"></li>
                                                <li>
                                                    <a href="#" onclick="deleteSubject(<?php echo $row['subject_id'];?>, <?php echo $class_id;?>);" class="text-red-600">
                                                        <i class="entypo-trash"></i> Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Import Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-10">
                        <!-- Mass Import -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-8 rounded-r-lg">
                            <h4 class="text-xl font-bold text-blue-900 mb-4">Import All Subjects (Academic Year)</h4>
                            <p class="text-base text-blue-800 mb-6 leading-relaxed">Import all subjects from the previous academic year for all classes</p>
                            <?php echo form_open(site_url('admin/subject/import'), array('id' => 'subj_import_form_mass'));?>
                                <button type="submit" class="btn-uniform bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-all transform hover:scale-105 w-full">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    Import All Subjects
                                </button>
                            </form>
                        </div>

                        <!-- Class-Specific Import -->
                        <?php if($subjects_rows != 0): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-8 rounded-r-lg">
                            <h4 class="text-xl font-bold text-green-900 mb-4">Copy Subjects to Another Class</h4>
                            <p class="text-base text-green-800 mb-6 leading-relaxed">Create the same subjects for a different class</p>
                            <?php echo form_open(site_url('admin/subject/import'), array('id' => 'subj_import_form'));?>
                                <div class="flex gap-2">
                                    <select name="class_id" class="form-control select2" id="new_class_id" style="width:100%;" required>
                                        <option value="">Select Class</option>
                                        <?php getFullClassList(); ?>
                                    </select>
                                    <button type="submit" class="btn-uniform bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-all whitespace-nowrap">
                                        Add Subjects
                                    </button>
                                </div>
                                <div id="same_class_id_error" class="text-red-600 mt-2" style="display: none;"></div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ADD TAB -->
            <div class="tab-pane" id="add">
                <div class="p-8">
                    <!-- Bulk Import Section -->
                    <div class="bg-purple-50 border-l-4 border-purple-500 p-8 rounded-r-lg mb-10">
                        <h4 class="text-2xl font-bold text-purple-900 mb-5 flex items-center gap-3">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Bulk Import from Excel
                        </h4>
                        <p class="text-base text-purple-800 mb-6 leading-relaxed">Upload an Excel file with multiple subjects at once</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <button type="button" onclick="downloadTemplate()" class="btn-uniform bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-all transform hover:scale-105 w-full">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download Template
                            </button>
                            
                            <label class="btn-uniform bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-all transform hover:scale-105 cursor-pointer text-center w-full">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <span id="excel_file_label">Choose Excel File</span>
                                <input type="file" id="excel_file" class="hidden" accept=".xlsx,.xls" onchange="updateFileName()">
                            </label>
                            
                            <button type="button" onclick="uploadExcel()" id="upload_excel_btn" class="btn-uniform bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-all transform hover:scale-105 w-full" disabled>
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Upload & Import
                            </button>
                        </div>
                    </div>



                    <!-- Single Subject Form -->
                    <div class="bg-white border border-gray-200 rounded-lg p-8">
                        <h4 class="text-2xl font-bold text-gray-900 mb-8 flex items-center gap-3">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Subject(s)
                        </h4>
                        
                        <form id="subject_form" <?php echo form_open('admin/subject/create_bulk'); ?>
                            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                            <input type="hidden" name="year" value="<?php echo $year; ?>">
                            <input type="hidden" name="term" value="<?php echo $term; ?>">
                            <input type="hidden" name="semester" value="<?php echo $sem; ?>">
                            
                            <div class="overflow-x-auto">
                                <table class="w-full border border-gray-300" id="subjects_table">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="border border-gray-300 px-4 py-3 text-left font-semibold">Subject Name</th>
                                            <th class="border border-gray-300 px-4 py-3 text-left font-semibold">Teacher</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="subjects_tbody">
                                        <tr>
                                            <td class="border border-gray-300 px-4 py-3">
                                                <input type="text" name="subjects[0][name]" class="w-full border-0 focus:ring-0" placeholder="Enter subject name" required>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-3">
                                                <select name="subjects[0][teacher_id]" class="w-full border-0 focus:ring-0">
                                                    <option value="">Select Teacher</option>
                                                    <?php
                                                    $teachers = $this->db->get('teacher')->result_array();
                                                    foreach($teachers as $teacher):
                                                    ?>
                                                        <option value="<?php echo $teacher['teacher_id']; ?>"><?php echo $teacher['name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <button type="button" onclick="removeRow(this)" class="text-red-600 hover:text-red-800">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-6 flex justify-between">
                                <button type="button" onclick="addRow()" class="btn-uniform bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-all">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Row
                                </button>
                                
                                <button type="submit" class="btn-uniform bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold py-4 px-10 rounded-lg shadow-lg transition-all">
                                    <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Save All Subjects
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loading_modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 40px; border-radius: 10px; text-align: center;">
        <img src="<?php echo base_url();?>assets/images/validate.gif" width="64px">
        <p style="margin-top: 20px; font-weight: bold;" id="loading_text">Processing...</p>
    </div>
</div>

<a href="" download="bulk_subjects.xlsx" style="display: none;" id="template_download">Download</a>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Select an option',
        allowClear: true
    });

    // DataTable
    $('#table_export').dataTable();

    // Show/hide core subject checkbox - removed as not needed for bulk creation
});
    // Mass subject form submission
    $(document).on('submit', '#mass_subject_form', function(e) {
        e.preventDefault();
        showAjaxModal_alert('Saving subjects...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(response.message || 'Operation failed', 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred', 'error');
        });
    });

    // Subject form submission
    $(document).on('submit', '#subject_form', function(e) {
        e.preventDefault();
        showAjaxModal_alert('Saving subjects...', 'loading');
        
        $.ajax({
            url: '<?php echo site_url('admin/subject/create_bulk'); ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(response.message || 'Operation failed', 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred', 'error');
        });
    });

    // Subject edit form submission (for modal)
    $(document).on('submit', '#subject_edit_form', function(e) {
        e.preventDefault();
        $('.close')[0].click();
        showAjaxModal_alert('Updating...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(response.message || 'Operation failed', 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred', 'error');
        });
    });

    // Class-specific import
    $('#subj_import_form').submit(function(e) {
        e.preventDefault();
        const new_class_id = $('#new_class_id').val();
        const old_class_id = '<?php echo $class_id; ?>';
        const year = '<?php echo $year; ?>';
        let term = '<?php echo $term; ?>';
        let class_name = '<?php echo $class_name; ?>';

        if(class_name == 'JHSS') {
            term = '<?php echo $sem; ?>';
        }

        if(new_class_id == old_class_id) {
            $('#same_class_id_error').show().text('Error! Please select a different class');
            return false;
        }

        $('#loading_modal').show();
        $('#loading_text').text('Importing subjects...');

        $.ajax({
            url: '<?php echo site_url('admin/do_subjects_import/') ?>' + new_class_id + '/' + old_class_id + '/' + year + '/' + term + '/' + class_name,
            type: 'POST'
        }).done(function(response) {
            $('#loading_modal').hide();
            if(response == 'success') {
                showAjaxModal_alert('Subjects successfully added', 'success');
                setTimeout(() => window.location.href = '<?php echo site_url('admin/subject/'); ?>' + new_class_id, 2000);
            } else if(response == 'error') {
                showAjaxModal_alert('This class already has subjects registered', 'error');
            } else {
                showAjaxModal_alert('Subjects were not added', 'error');
            }
        }).fail(function() {
            $('#loading_modal').hide();
            showAjaxModal_alert('An error occurred', 'error');
        });
    });

    // Mass import
    $('#subj_import_form_mass').submit(function(e) {
        e.preventDefault();
        $('#loading_modal').show();
        $('#loading_text').text('Importing all subjects...');

        $.ajax({
            url: '<?php echo site_url('admin/do_subjects_import_mass') ?>',
            type: 'POST'
        }).done(function(response) {
            $('#loading_modal').hide();
            if(response == 'error') {
                showAjaxModal_alert('Subjects were already registered for this year', 'error');
            } else {
                showAjaxModal_alert('Subjects successfully imported', 'success');
                setTimeout(() => location.reload(), 2000);
            }
        }).fail(function() {
            $('#loading_modal').hide();
            showAjaxModal_alert('An error occurred', 'error');
        });
    });


// Mass addition functions
let rowIndex = 1;

function addRow() {
    const tbody = document.getElementById('subjects_tbody');
    const newRow = document.createElement('tr');
    
    newRow.innerHTML = `
        <td class="border border-gray-300 px-4 py-3">
            <input type="text" name="subjects[${rowIndex}][name]" class="w-full border-0 focus:ring-0" placeholder="Enter subject name" required>
        </td>
        <td class="border border-gray-300 px-4 py-3">
            <select name="subjects[${rowIndex}][teacher_id]" class="w-full border-0 focus:ring-0">
                <option value="">Select Teacher</option>
                <?php foreach($teachers as $teacher): ?>
                    <option value="<?php echo $teacher['teacher_id']; ?>"><?php echo $teacher['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="border border-gray-300 px-4 py-3 text-center">
            <button type="button" onclick="removeRow(this)" class="text-red-600 hover:text-red-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    rowIndex++;
}

function removeRow(button) {
    const tbody = document.getElementById('subjects_tbody');
    if(tbody.children.length > 1) {
        button.closest('tr').remove();
    } else {
        showAjaxModal_alert('At least one row is required', 'warning');
    }
}

// Excel functions
function updateFileName() {
    const file = document.getElementById('excel_file').files[0];
    if(file) {
        document.getElementById('excel_file_label').innerHTML = '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>' + file.name;
        document.getElementById('upload_excel_btn').disabled = false;
    }
}

function downloadTemplate() {
    $.ajax({
        url: '<?php echo site_url('admin/generate_subject_template'); ?>',
        success: function(response) {
            $('#template_download').attr('href', response);
            $('#template_download')[0].click();
            showAjaxModal_alert('Template downloaded successfully', 'success', false);
        }
    });
}

function uploadExcel() {
    const file = document.getElementById('excel_file').files[0];
    if(!file) {
        showAjaxModal_alert('Please select a file', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('excel_file', file);

    $('#loading_modal').show();
    $('#loading_text').text('Uploading and processing...');

    $.ajax({
        url: '<?php echo site_url('admin/bulk_subject_import_excel'); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function(response) {
        $('#loading_modal').hide();
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        $('#loading_modal').hide();
        showAjaxModal_alert('An error occurred during upload', 'error');
    });
}

// Delete subject function
function deleteSubject(subject_id, class_id) {
    showConfirmModal(
        'Confirm Delete',
        'Are you sure you want to delete this subject?',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/subject/delete/'); ?>' + subject_id + '/' + class_id,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    // Show success modal without auto-reload
                    showAjaxModal_alert(response.message || 'Subject Deleted Successfully', 'success', false);
                    
                    // Reload page after 2 seconds to show the updated list
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAjaxModal_alert(response.message || 'Failed to delete subject', 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Delete',
        'danger'
    );
}
</script>
