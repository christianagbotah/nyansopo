<?php 
$edit_data = $this->db->get_where('class', array('class_id' => $param2))->result_array();
foreach ($edit_data as $row):
?>

<style>
    .edit-class-modal label { font-size: 15px !important; font-weight: 600; }
    .edit-class-modal select, .edit-class-modal input { font-size: 15px !important; }
    .edit-class-modal option { font-size: 15px !important; }
    .edit-class-modal h3 { font-size: 20px !important; }
</style>

<div class="edit-class-modal max-w-5xl mx-auto p-4">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="entypo-pencil"></i>
                <?php echo get_phrase('edit_class');?>
            </h3>
        </div>
        
        <div class="p-6">
            <?php echo form_open(site_url('admin/classes/do_update/'.$row['class_id']), array('id' => 'class_form_edit'));?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-900"><?php echo get_phrase('name');?></label>
                    <select name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3" required>
                        <?php
                        $class_names = ['CRECHE', 'NURSERY', 'KG', 'BASIC', 'JHS'];
                        foreach($class_names as $cn) {
                            $selected = ($row['name'] == $cn) ? 'selected' : '';
                            echo '<option value="'.$cn.'" '.$selected.'>'.$cn.'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-900"><?php echo get_phrase('category');?></label>
                    <select name="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3" required>
                        <option value="Pre-School" <?php if($row['category'] == 'Pre-School') echo 'selected';?>>Pre-School</option>
                        <option value="Lower Primary" <?php if($row['category'] == 'Lower Primary') echo 'selected';?>>Lower Primary</option>
                        <option value="Upper Primary" <?php if($row['category'] == 'Upper Primary') echo 'selected';?>>Upper Primary</option>
                        <option value="JHS" <?php if($row['category'] == 'JHS') echo 'selected';?>>JHS</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-900"><?php echo get_phrase('numeric_name');?></label>
                    <input type="text" name="name_numeric" value="<?php echo $row['name_numeric'];?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3" placeholder="e.g., 1, 2, 3"/>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-900"><?php echo get_phrase('section');?></label>
                    <select name="section_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3" required>
                        <?php 
                        $this->db->select('name');
                        $this->db->distinct();
                        $this->db->from('section');
                        $sections = $this->db->get()->result_array();
                        $set_sec = trim($this->db->get_where('section', array('class_id' => $param2))->row()->name);
                        foreach($sections as $row_s):
                        ?>
                        <option value="<?php echo $row_s['name'];?>" <?php if($set_sec == trim($row_s['name'])) echo 'selected'; ?>><?php echo $row_s['name'];?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-900"><?php echo get_phrase('teacher');?></label>
                    <select name="teacher_id" id="teacher_select_edit" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3">
                        <option value=""><?php echo get_phrase('select_teacher');?></option>
                        <?php 
                        $teachers = $this->db->get('teacher')->result_array();
                        foreach($teachers as $row2):
                        ?>
                        <option value="<?php echo $row2['teacher_id'];?>" <?php if($row['teacher_id'] == $row2['teacher_id']) echo 'selected';?>><?php echo $row2['name'];?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-semibold rounded-lg text-base px-5 py-3 flex items-center justify-center gap-2">
                        <i class="entypo-check"></i>
                        <?php echo get_phrase('update_class');?>
                    </button>
                </div>
            </div>
            <?php echo form_close();?>
        </div>
    </div>
</div>

<?php endforeach; ?>

<script type="text/javascript">
    $('#teacher_select_edit').select2({
        placeholder: '<?php echo get_phrase('select_teacher');?>',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#teacher_select_edit').parent()
    });
    
    $('#class_form_edit').submit(function(event) {
        event.preventDefault();
        $('.close')[0].click();
        showAjaxModal_alert('<?php echo get_phrase('updating_class');?>...', 'loading');
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            dataType: 'json',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
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
