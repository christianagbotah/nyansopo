<?php $theme_color = get_settings('theme_color') ?: '#667eea'; ?>
<div class="msg-body-header" style="background: <?php echo $theme_color; ?>;">
    <h3><i class="fa fa-pen"></i> <?php echo get_phrase('write_new_message'); ?></h3>
    <p><?php echo get_phrase('compose_new_message'); ?></p>
</div>

<div class="msg-content">
    <?php echo form_open(site_url('admin/message/send_new/'), array('id' => 'msgComposeForm', 'enctype' => 'multipart/form-data')); ?>
    
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                <i class="fa fa-user"></i> <?php echo get_phrase('recipient'); ?>:
            </label>
            <select class="form-control select2" name="reciever" required style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                <option value=""><?php echo get_phrase('select_a_user'); ?></option>
                
                <optgroup label="<?php echo get_phrase('active_student'); ?>">
                    <?php
                    $running_year = get_settings('running_year');
                    $running_term = get_settings('running_term');
                    $students = $this->db->get_where('enroll', array('year' => $running_year, 'mute' => '0', 'term' => $running_term))->result_array();
                    foreach ($students as $row):
                        $student = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
                        ?>
                        <option value="student-<?php echo $student->student_id; ?>">
                            <?php echo $student->name; ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                
                <optgroup label="<?php echo get_phrase('teacher'); ?>">
                    <?php
                    $teachers = $this->db->get_where('teacher', array('block_limit' => '0'))->result_array();
                    foreach ($teachers as $row): ?>
                        <option value="teacher-<?php echo $row['teacher_id']; ?>">
                            <?php echo $row['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                
                <optgroup label="<?php echo get_phrase('active_parent'); ?>">
                    <?php
                    $this->db->select('student_id');
                    $this->db->from('enroll');
                    $this->db->where('year', $running_year);
                    $this->db->where('term', $running_term);
                    $this->db->where('mute', '0');
                    $st_ids = $this->db->get()->result_array();
                    $st_ids_array = array_column($st_ids, 'student_id');
                    
                    if(!empty($st_ids_array)) {
                        $this->db->select('parent_id');
                        $this->db->from('student');
                        $this->db->where_in('student_id', $st_ids_array);
                        $pt_ids = $this->db->get()->result_array();
                        $pt_ids_array = array_column($pt_ids, 'parent_id');
                        
                        if(!empty($pt_ids_array)) {
                            $this->db->where_in('parent_id', $pt_ids_array);
                            $parents = $this->db->get('parent')->result_array();
                            foreach ($parents as $row): ?>
                                <option value="parent-<?php echo $row['parent_id']; ?>">
                                    <?php echo $row['name']; ?>
                                </option>
                            <?php endforeach;
                        }
                    }
                    ?>
                </optgroup>
            </select>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                <i class="fa fa-message"></i> <?php echo get_phrase('message'); ?>:
            </label>
            <textarea name="message" required 
                      style="width: 100%; min-height: 200px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical;"
                      placeholder="<?php echo get_phrase('write_your_message'); ?>..."></textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                <i class="fa fa-paperclip"></i> <?php echo get_phrase('attachment'); ?>: <span style="font-weight: 400; color: #9ca3af; font-size: 13px;">(<?php echo get_phrase('optional'); ?>, max 4MB)</span>
            </label>
            <input type="file" class="form-control" name="attached_file_on_messaging" 
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                   style="padding: 10px; border: 2px dashed #e5e7eb; border-radius: 8px;">
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn-send" style="flex: 1;">
                <i class="fa fa-paper-plane"></i> <?php echo get_phrase('send_message'); ?>
            </button>
            <button type="button" onclick="navigation('<?php echo site_url('admin/message'); ?>')" 
                    style="padding: 12px 24px; background: #6b7280; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i class="fa fa-times"></i> <?php echo get_phrase('cancel'); ?>
            </button>
        </div>
    </div>
    
    </form>
</div>

<script>
$('#msgComposeForm').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('<?php echo get_phrase("sending"); ?>...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false
    }).done(function(response) {
        showAjaxModal_alert('<?php echo get_phrase("message_sent_successfully"); ?>', 'success');
        setTimeout(() => navigation('<?php echo site_url('admin/message'); ?>'), 2000);
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
    });
});
</script>
