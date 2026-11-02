<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo get_phrase('manage_online_exams');?></h2>
            <p class="text-gray-600"><?php echo get_phrase('create_and_manage_online_examinations');?></p>
        </div>
        <div class="flex gap-3">
            <a href="<?php echo site_url('teacher/manage_online_exam');?>" class="inline-flex items-center px-6 py-3 <?php echo $status == 'active' ? 'bg-gradient-to-r from-green-500 to-emerald-500' : 'bg-white border-2 border-gray-300'; ?> text-<?php echo $status == 'active' ? 'white' : 'gray-700'; ?> font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                <i class="entypo-check mr-2"></i>
                <?php echo get_phrase('active_exams');?>
            </a>
            <a href="<?php echo site_url('teacher/manage_online_exam/expired');?>" class="inline-flex items-center px-6 py-3 <?php echo $status == 'expired' ? 'bg-gradient-to-r from-red-500 to-pink-500' : 'bg-white border-2 border-gray-300'; ?> text-<?php echo $status == 'expired' ? 'white' : 'gray-700'; ?> font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                <i class="entypo-clock mr-2"></i>
                <?php echo get_phrase('expired_exams');?>
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full datatable" id="table_export">
            <thead class="bg-gradient-to-r from-cyan-500 to-blue-500 text-white">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-book-open mr-2"></i><?php echo get_phrase('exam_name');?>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-users mr-2"></i><?php echo get_phrase('class_and_section');?>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-graduation-cap mr-2"></i><?php echo get_phrase('subject');?>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-calendar mr-2"></i><?php echo get_phrase('exam_date');?>
                    </th>
                    <th class="px-6 py-4 text-center text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-info mr-2"></i><?php echo get_phrase('status');?>
                    </th>
                    <th class="px-6 py-4 text-center text-sm font-semibold uppercase tracking-wider">
                        <i class="entypo-cog mr-2"></i><?php echo get_phrase('actions');?>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php 
                $displayed_exams = [];
                foreach($online_exams as $row):
                    $class_teacher = $this->db->get_where('class', array('class_id' => $row['class_id']))->row()->teacher_id;
                    $subject_teacher = $this->db->get_where('subject', array('subject_id' => $row['subject_id']))->row()->teacher_id;
                    
                    if($class_teacher == $this->session->userdata('teacher_id') || $subject_teacher == $this->session->userdata('teacher_id')):
                        if(in_array($row['online_exam_id'], $displayed_exams)) continue;
                        $displayed_exams[] = $row['online_exam_id'];
                ?>
                <tr class="hover:bg-cyan-50 transition-colors duration-150">
                    <td class="px-6 py-4">
                        <a href="<?php echo site_url('teacher/manage_online_exam_question/').$row['online_exam_id']; ?>" class="text-cyan-600 hover:text-cyan-900 font-semibold flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-lg flex items-center justify-center text-white font-bold mr-3">
                                <i class="entypo-doc-text"></i>
                            </div>
                            <?php echo $row['title'];?>
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 mb-1">
                                <i class="entypo-graduation-cap mr-1"></i>
                                <?php echo $this->db->get_where('class', array('class_id' => $row['class_id']))->row()->name.' '.$this->db->get_where('class', array('class_id' => $row['class_id']))->row()->name_numeric;?>
                            </span>
                            <br>
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
                                <i class="entypo-users mr-1"></i>
                                <?php echo $this->db->get_where('section', array('section_id' => $row['section_id']))->row()->name;?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                            <?php echo $this->db->get_where('subject', array('subject_id' => $row['subject_id'], 'year' => $running_year))->row()->name;?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <div class="font-semibold text-gray-900 mb-1">
                                <i class="entypo-calendar text-cyan-500 mr-1"></i>
                                <?php echo date('M d, Y', $row['exam_date']);?>
                            </div>
                            <div class="text-gray-600">
                                <i class="entypo-clock text-cyan-500 mr-1"></i>
                                <?php echo $row['time_start'].' - '.$row['time_end'];?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if($row['status'] == 'published'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                <?php echo get_phrase('published');?>
                            </span>
                        <?php elseif($row['status'] == 'pending'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                <?php echo get_phrase('pending');?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                <?php echo get_phrase('expired');?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2 flex-wrap">
                            <a href="<?php echo site_url('teacher/manage_online_exam_question/').$row['online_exam_id']; ?>" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                <i class="entypo-cog mr-1"></i><?php echo get_phrase('questions');?>
                            </a>
                            <a href="<?php echo site_url('teacher/update_online_exam/').$row['online_exam_id']; ?>" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                <i class="entypo-pencil mr-1"></i><?php echo get_phrase('edit');?>
                            </a>
                            <?php if ($row['status'] == 'pending'): ?>
                                <a href="#" onclick="confirm_modal('<?php echo site_url('teacher/manage_online_exam_status/'.$row['online_exam_id'].'/published'); ?>', 'generic_confirmation');" class="inline-flex items-center px-3 py-1 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                    <i class="entypo-share mr-1"></i><?php echo get_phrase('publish');?>
                                </a>
                            <?php elseif ($row['status'] == 'published'): ?>
                                <a href="#" onclick="confirm_modal('<?php echo site_url('teacher/manage_online_exam_status/'.$row['online_exam_id'].'/expired'); ?>', 'generic_confirmation');" class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                    <i class="entypo-cancel mr-1"></i><?php echo get_phrase('cancel');?>
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo site_url('teacher/view_online_exam_result/'.$row['online_exam_id']); ?>" class="inline-flex items-center px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                <i class="entypo-eye mr-1"></i><?php echo get_phrase('results');?>
                            </a>
                            <a href="#" onclick="confirm_modal('<?php echo site_url('teacher/manage_online_exam/delete/'.$row['online_exam_id']);?>');" class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                <i class="entypo-trash mr-1"></i><?php echo get_phrase('delete');?>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php 
                    endif;
                endforeach;
                ?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#table_export').dataTable({
        "pageLength": 25,
        "order": [[3, "desc"]],
        "language": {
            "search": "Search exams:",
            "lengthMenu": "Show _MENU_ exams per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ exams",
            "infoEmpty": "No exams available",
            "infoFiltered": "(filtered from _MAX_ total exams)"
        }
    });
});
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
