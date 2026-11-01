<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo get_phrase('my_subjects');?></h2>
            <p class="text-gray-600"><?php echo $class_name.' '.$this->crud_model->get_class_name_numeric($class_id);?> - <?php echo get_phrase('year');?>: <?php echo $year;?> | <?php echo get_phrase($class_name == 'JHSS' ? 'semester' : 'term');?>: <?php echo $class_name == 'JHSS' ? $sem : $term;?></p>
        </div>
        <div class="bg-white rounded-lg p-4 shadow-md">
            <div class="text-center">
                <p class="text-sm text-gray-500 mb-1"><?php echo get_phrase('total_subjects');?></p>
                <p class="text-3xl font-bold text-indigo-600"><?php echo $subjects_rows;?></p>
            </div>
        </div>
    </div>
</div>

<?php if($subjects_rows < 1): ?>
<div class="bg-white rounded-xl shadow-sm p-12 text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
        <i class="entypo-info text-4xl text-red-500"></i>
    </div>
    <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('no_subjects_assigned');?></h3>
    <p class="text-gray-600 mb-6"><?php echo get_phrase('you_do_not_have_any_subjects_assigned_for_this_class');?></p>
    <p class="text-sm text-gray-500"><?php echo get_phrase('please_contact_the_administrator_for_assistance');?></p>
</div>
<?php else: ?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full datatable" id="table_export">
            <thead class="bg-gradient-to-r from-indigo-500 to-blue-500 text-white">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-book-open mr-2"></i><?php echo get_phrase('subject_name');?>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-users mr-2"></i><?php echo get_phrase('class');?>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-user mr-2"></i><?php echo get_phrase('teacher');?>
                    </th>
                    <th class="px-6 py-4 text-center text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-check mr-2"></i><?php echo get_phrase('status');?>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php 
                $count = 1;
                foreach($subjects as $row):
                    $class = $this->db->get_where('class', array('class_id' => $row['class_id']))->result_array();
                    foreach ($class as $c):
                        $section_name = $this->db->get_where('section', array('class_id' => $c['class_id']))->row()->name;
                        $class_has_more_sections = $this->db->get_where('class', array('name' => $c['name'], 'name_numeric' => $c['name_numeric']))->num_rows();
                        $sec_name = '';
                        if($class_has_more_sections > 1) {
                            $sec_name = $section_name;
                        }
                ?>
                <tr class="hover:bg-indigo-50 transition-colors duration-150">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-indigo-400 to-blue-500 rounded-lg flex items-center justify-center text-white font-bold">
                                <?php echo substr($row['name'], 0, 1);?>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-semibold text-gray-900"><?php echo $row['name'];?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <i class="entypo-graduation-cap mr-1"></i>
                            <?php echo $this->crud_model->get_type_name_by_id('class',$row['class_id']).' '.$c['name_numeric'].$sec_name;?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="<?php echo site_url($account_type.'/teacher_list'); ?>" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center">
                            <i class="entypo-user mr-1"></i>
                            <?php echo $this->crud_model->get_type_name_by_id('teacher',$row['teacher_id']);?>
                        </a>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if($row['status'] == 1): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                <?php echo get_phrase('active');?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                <?php echo get_phrase('inactive');?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php 
                    endforeach;
                endforeach;
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var $result = <?php echo $subjects_rows; ?>;
    if($result < 1) {
        $('#table_export').removeAttr('id');
    } else {
        $("#table_export").dataTable({
            "pageLength": 25,
            "order": [[0, "asc"]],
            "language": {
                "search": "Search subjects:",
                "lengthMenu": "Show _MENU_ subjects per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ subjects",
                "infoEmpty": "No subjects available",
                "infoFiltered": "(filtered from _MAX_ total subjects)"
            }
        });
    }
    
    $(".dataTables_wrapper select").select2({
        minimumResultsForSearch: -1
    });
});
</script>

<style>
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.5rem 1rem;
    margin: 0 0.25rem;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    background: white;
    color: #4b5563;
    transition: all 0.2s;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    margin-left: 0.5rem;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.5rem 2rem 0.5rem 1rem;
}
</style>
