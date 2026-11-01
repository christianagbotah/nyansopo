<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo get_phrase('class_time_table');?></h2>
            <p class="text-gray-600"><?php echo $this->db->get_where('class', array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;?></p>
        </div>
        <div class="flex gap-3">
            <a href="<?php echo site_url('teacher/class_routine_add/'.$class_id);?>" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                <i class="entypo-plus-circled mr-2"></i>
                <?php echo get_phrase('add_class_time_table');?>
            </a>
        </div>
    </div>
</div>

<?php
$class_name = $this->crud_model->get_class_name($class_id);
$query = $this->db->get_where('section', array('class_id' => $class_id));

if($query->num_rows() > 0):
    $sections = $query->result_array();
    foreach($sections as $row):
?>

<div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4 flex items-center justify-between">
        <div class="text-white">
            <h3 class="text-xl font-bold">
                <?php echo $this->db->get_where('class', array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;?> | 
                <?php echo get_phrase('section');?> - <?php echo $this->db->get_where('section', array('section_id' => $row['section_id']))->row()->name;?>
            </h3>
        </div>
        <a href="<?php echo site_url('teacher/class_routine_print_view/'.$class_id.'/'.$row['section_id']);?>" target="_blank" class="inline-flex items-center px-4 py-2 bg-white text-purple-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors duration-200">
            <i class="entypo-print mr-2"></i>
            <?php echo get_phrase('print');?>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <tbody class="divide-y divide-gray-200">
                <?php 
                $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                $day_colors = [
                    'sunday' => 'from-red-500 to-orange-500',
                    'monday' => 'from-blue-500 to-cyan-500',
                    'tuesday' => 'from-green-500 to-emerald-500',
                    'wednesday' => 'from-yellow-500 to-amber-500',
                    'thursday' => 'from-purple-500 to-violet-500',
                    'friday' => 'from-pink-500 to-rose-500',
                    'saturday' => 'from-indigo-500 to-blue-500'
                ];
                
                foreach($days as $day):
                ?>
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 w-40">
                        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r <?php echo $day_colors[$day]; ?> text-white font-bold rounded-lg shadow-md">
                            <i class="entypo-calendar mr-2"></i>
                            <?php echo strtoupper($day);?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-3">
                            <?php
                            $this->db->order_by("time_start", "asc");
                            $this->db->where('day', $day);
                            $this->db->where('class_id', $class_id);
                            $this->db->where('section_id', $row['section_id']);
                            
                            if($class_name == 'JHSS') {
                                $this->db->where('sem', $running_sem);
                            } else {
                                $this->db->where('term', $running_term);
                            }
                            $this->db->where('year', $running_year);
                            $routines = $this->db->get('class_routine')->result_array();
                            
                            if(empty($routines)):
                            ?>
                                <span class="text-gray-400 italic">No classes scheduled</span>
                            <?php
                            else:
                                foreach($routines as $row2):
                                    $teacher_id = $this->db->get_where('subject', array('subject_id' => $row2['subject_id']))->row()->teacher_id;
                                    $this_teacher = $this->session->userdata('teacher_id');
                                    $is_my_subject = $teacher_id == $this_teacher;
                            ?>
                            <div class="group relative">
                                <div class="inline-flex items-center px-4 py-2 <?php echo $is_my_subject ? 'bg-gradient-to-r from-green-500 to-emerald-500' : 'bg-gradient-to-r from-gray-500 to-slate-500'; ?> text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                                    <i class="entypo-book mr-2"></i>
                                    <div>
                                        <div class="text-sm font-bold">
                                            <?php echo strtoupper(strtolower($this->crud_model->get_subject_name_by_id($row2['subject_id'])));?>
                                        </div>
                                        <div class="text-xs opacity-90">
                                            <?php
                                            if ($row2['time_start_min'] == 0 && $row2['time_end_min'] == 0) 
                                                echo $row2['time_start'].':00 - '.$row2['time_end'].':00';
                                            else
                                                echo $row2['time_start'].':'.$row2['time_start_min'].' - '.$row2['time_end'].':'.$row2['time_end_min'];
                                            ?>
                                        </div>
                                    </div>
                                    <?php if($is_my_subject): ?>
                                        <span class="ml-2 inline-flex items-center justify-center w-6 h-6 bg-white bg-opacity-30 rounded-full">
                                            <i class="entypo-user text-xs"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
    endforeach;
else:
?>
<div class="bg-white rounded-xl shadow-sm p-12 text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 rounded-full mb-4">
        <i class="entypo-info text-4xl text-purple-500"></i>
    </div>
    <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('no_sections_found');?></h3>
    <p class="text-gray-600"><?php echo get_phrase('please_add_sections_to_this_class_first');?></p>
</div>
<?php 
endif;
?>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.group:hover .group-hover\:block {
    display: block;
}
</style>
