<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('class_routine');?></h2>
        <p class="text-gray-600"><?php echo get_phrase('manage_class_schedules_and_timetables');?></p>
    </div>

    <div class="flex gap-2 border-b mb-6">
        <button onclick="showTab('list')" class="tab-btn active px-6 py-3 font-medium text-blue-600 border-b-2 border-blue-600" id="btn-list">
            <i class="entypo-menu"></i> <?php echo get_phrase('class_routine_list');?>
        </button>
        <button onclick="showTab('add')" class="tab-btn px-6 py-3 font-medium text-gray-600 hover:text-blue-600" id="btn-add">
            <i class="entypo-plus-circled"></i> <?php echo get_phrase('add_class_routine');?>
        </button>
    </div>

    <div class="tab-content">
        <div class="tab-pane" id="list">
            <div class="space-y-4">
                <?php 
                $toggle = true;
                $classes = $this->db->get('class')->result_array();
                foreach($classes as $row):
                ?>
                <div class="bg-white border rounded-lg overflow-hidden">
                    <button onclick="toggleClass(<?php echo $row['class_id'];?>)" class="w-full flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition">
                        <span class="font-semibold text-gray-800">
                            <i class="entypo-rss text-blue-600"></i> Class <?php echo $row['name'];?>
                        </span>
                        <i class="entypo-down-open text-gray-600" id="icon-<?php echo $row['class_id'];?>"></i>
                    </button>
                    <div id="collapse<?php echo $row['class_id'];?>" class="<?php echo $toggle ? '' : 'hidden'; $toggle=false;?>">
                        <div class="p-4">
                                        <?php
                                            $query_for_section = $this->db->get_where('section' , array(
                                                'class_id' => $row['class_id']));
                                            if($query_for_section->num_rows() <= 0):
                                        ?>

                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <tbody>
                                                <?php 
                                                for($d=1;$d<=7;$d++):
                                                
                                                if($d==1)$day='sunday';
                                                else if($d==2)$day='monday';
                                                else if($d==3)$day='tuesday';
                                                else if($d==4)$day='wednesday';
                                                else if($d==5)$day='thursday';
                                                else if($d==6)$day='friday';
                                                else if($d==7)$day='saturday';
                                                ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="p-3 font-semibold text-gray-700 bg-gray-50 w-32"><?php echo strtoupper($day);?></td>
                                            <td class="p-3">
                                                        <?php
                                                        $this->db->order_by("time_start", "asc");
                                                        $this->db->where('day' , $day);
                                                        $this->db->where('class_id' , $row['class_id']);
                                                        $routines   =   $this->db->get('class_routine')->result_array();
                                                        foreach($routines as $row2):
                                                        ?>
                                                <div class="inline-block mr-2 mb-2">
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 group relative">
                                                        <span class="text-sm font-medium text-blue-900">
                                                            <?php echo $this->crud_model->get_subject_name_by_id($row2['subject_id']);?>
                                                        </span>
                                                        <span class="text-xs text-blue-600 ml-2">
                                                            <?php
                                                                if ($row2['time_start_min'] == 0 && $row2['time_end_min'] == 0) 
                                                                    echo '('.$row2['time_start'].'-'.$row2['time_end'].')';
                                                                if ($row2['time_start_min'] != 0 || $row2['time_end_min'] != 0)
                                                                    echo '('.$row2['time_start'].':'.$row2['time_start_min'].'-'.$row2['time_end'].':'.$row2['time_end_min'].')';
                                                            ?>
                                                        </span>
                                                        <div class="hidden group-hover:flex absolute right-0 top-full mt-1 bg-white shadow-lg rounded-lg border z-10">
                                                            <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_edit_class_routine/'.$row2['class_routine_id']);?>');" class="px-3 py-2 text-sm hover:bg-gray-100 flex items-center gap-2">
                                                                <i class="entypo-pencil text-blue-600"></i> <?php echo get_phrase('edit');?>
                                                            </a>
                                                            <a href="#" onclick="confirm_modal('<?php echo site_url('admin/class_routine/delete/'.$row2['class_routine_id']);?>');" class="px-3 py-2 text-sm hover:bg-gray-100 flex items-center gap-2">
                                                                <i class="entypo-trash text-red-600"></i> <?php echo get_phrase('delete');?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                        <?php endforeach;?>

                                            </td>
                                        </tr>
                                        <?php endfor;?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif;?>
                        </div>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>
        <div class="tab-pane hidden" id="add">
            <div class="max-w-3xl">
                <?php echo form_open(site_url('admin/class_routine/create'), array('class' => 'space-y-4','target'=>'_top'));?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('class');?></label>
                        <select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="return get_class_section_subject(this.value)">
                            <option value=""><?php echo get_phrase('select_class');?></option>
                            <?php getFullClassList($this->session->userdata('teacher_id')); ?>
                        </select>
                    </div>
                    <div id="section_subject_selection_holder"></div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('day');?></label>
                        <select name="day" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="sunday">Sunday</option>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('starting_time');?></label>
                        <div class="grid grid-cols-3 gap-3">
                            <select name="time_start" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value=""><?php echo get_phrase('hour');?></option>
                                <?php for($i = 0; $i <= 12; $i++):?>
                                    <option value="<?php echo $i;?>"><?php echo $i;?></option>
                                <?php endfor;?>
                            </select>
                            <select name="time_start_min" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value=""><?php echo get_phrase('minutes');?></option>
                                <?php for($i = 0; $i <= 11; $i++):?>
                                    <option value="<?php echo $i * 5;?>"><?php echo $i * 5;?></option>
                                <?php endfor;?>
                            </select>
                            <select name="starting_ampm" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="1">AM</option>
                                <option value="2">PM</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('ending_time');?></label>
                        <div class="grid grid-cols-3 gap-3">
                            <select name="time_end" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value=""><?php echo get_phrase('hour');?></option>
                                <?php for($i = 0; $i <= 12; $i++):?>
                                    <option value="<?php echo $i;?>"><?php echo $i;?></option>
                                <?php endfor;?>
                            </select>
                            <select name="time_end_min" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value=""><?php echo get_phrase('minutes');?></option>
                                <?php for($i = 0; $i <= 11; $i++):?>
                                    <option value="<?php echo $i * 5;?>"><?php echo $i * 5;?></option>
                                <?php endfor;?>
                            </select>
                            <select name="ending_ampm" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="1">AM</option>
                                <option value="2">PM</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition">
                        <?php echo get_phrase('add_class_routine');?>
                    </button>
                <?php echo form_close();?>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabId) {
    $('.tab-pane').addClass('hidden');
    $('#' + tabId).removeClass('hidden');
    $('.tab-btn').removeClass('active text-blue-600 border-blue-600').addClass('text-gray-600');
    $('#btn-' + tabId).addClass('active text-blue-600 border-b-2 border-blue-600').removeClass('text-gray-600');
}

function toggleClass(classId) {
    $('#collapse' + classId).toggleClass('hidden');
    $('#icon-' + classId).toggleClass('entypo-down-open entypo-up-open');
}

function get_class_section_subject(class_id) {
    $.ajax({
        url: '<?php echo site_url('admin/get_class_section_subject/');?>' + class_id,
        success: function(response) { $('#section_subject_selection_holder').html(response); }
    });
}

$(document).ready(function() { showTab('list'); });
</script>

