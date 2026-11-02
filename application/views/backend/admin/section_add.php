<div class="max-w-2xl mx-auto p-4">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
            <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                <i class="entypo-plus-circled"></i>
                <?php echo get_phrase('add_new_section');?>
            </h3>
        </div>
        
        <div class="p-6">
            <?php echo form_open(site_url('admin/sections/create'), array('id' => 'section_form_add'));?>
            <div class="space-y-6">
                <div>
                    <label class="block mb-2 text-base font-semibold text-gray-900"><?php echo get_phrase('name');?></label>
                    <input type="text" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3" required/>
                </div>

                <div>
                    <label class="block mb-2 text-base font-semibold text-gray-900"><?php echo get_phrase('nick_name');?></label>
                    <input type="text" name="nick_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3"/>
                </div>

                <div>
                    <label class="block mb-2 text-base font-semibold text-gray-900"><?php echo get_phrase('class');?></label>
                    <select name="class_id" id="class_select_add" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3" required>
                        <option value=""><?php echo get_phrase('select');?></option>
                        <?php 
                            $classes = $this->db->get('class')->result_array();
                            foreach($classes as $row):
                        ?>
                            <option value="<?php echo $row['class_id'];?>"><?php echo $row['name'].' '.$row['name_numeric'];?></option>
                        <?php endforeach;?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-base font-semibold text-gray-900"><?php echo get_phrase('teacher');?></label>
                    <select name="teacher_id" id="teacher_select_add" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3">
                        <option value=""><?php echo get_phrase('select');?></option>
                        <?php 
                            $teachers = $this->db->get('teacher')->result_array();
                            foreach($teachers as $row):
                        ?>
                            <option value="<?php echo $row['teacher_id'];?>"><?php echo $row['name'];?></option>
                        <?php endforeach;?>
                    </select>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" class="px-6 py-3 text-base font-semibold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300" data-dismiss="modal"><?php echo get_phrase('cancel');?></button>
                    <button type="submit" class="px-6 py-3 text-base font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700"><?php echo get_phrase('add_section');?></button>
                </div>
            </div>
            <?php echo form_close();?>
        </div>
    </div>
</div>

<script>
$('#class_select_add').select2({placeholder: '<?php echo get_phrase('select');?>', allowClear: true, width: '100%', dropdownParent: $('#class_select_add').parent()});
$('#teacher_select_add').select2({placeholder: '<?php echo get_phrase('select');?>', allowClear: true, width: '100%', dropdownParent: $('#teacher_select_add').parent()});

$('#section_form_add').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('creating_section');?>...', 'loading');
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
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed');?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred');?>', 'error');
    });
});
</script>
