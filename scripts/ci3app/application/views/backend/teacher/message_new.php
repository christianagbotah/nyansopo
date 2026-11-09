<div class="mail-header" style="padding-bottom: 27px ;">
    <!-- title -->
    <h3 class="mail-title">
        <?php echo get_phrase('write_new_message'); ?>
    </h3>
</div>

<div class="mail-compose">

    <?php echo form_open(site_url('teacher/message/send_new/'), array('id' => 'message_form', 'class' => 'form', 'enctype' => 'multipart/form-data')); ?>


    <div class="form-group">
        <label for="subject"><?php echo get_phrase('recipient'); ?>:</label>
        <br><br>
        <select class="form-control select2" name="reciever" required>

            <option value=""><?php echo get_phrase('select_a_user'); ?></option>
            <optgroup label="<?php echo get_phrase('admin'); ?>">
                <?php
                $admins = $this->db->get('admin')->result_array();
                foreach ($admins as $row):
                    ?>

                    <option value="admin-<?php echo $row['admin_id']; ?>">
                        - <?php echo $row['name']; ?></option>

                <?php endforeach; ?>
            </optgroup>
            <optgroup label="<?php echo get_phrase('student'); ?>">
                <?php
                $students = $this->db->get('student')->result_array();
                foreach ($students as $row):
                    ?>

                    <option value="student-<?php echo $row['student_id']; ?>">
                        - <?php echo $row['name']; ?></option>

                <?php endforeach; ?>
            </optgroup>
            <optgroup label="<?php echo get_phrase('active_parent'); ?>">
                <?php
                $this->db->select('student_id');
                $this->db->from('enroll');
                $this->db->where('mute', '0');
                $this->db->where('year', $running_year);
                $this->db->where('term', $running_term);
                $st_ids =  $this->db->get()->result_array();

                $st_ids_array = array();
                $i = 0;
                foreach($st_ids as $row) {
                    $st_ids_array[$i] = $row['student_id'];
                    $i++;
                }

                $this->db->select('parent_id');
                $this->db->from('student');
                $this->db->where_in('student_id', $st_ids_array);
                $pt_ids = $this->db->get()->result_array();

                $pt_ids_array = array();
                $j = 0;
                foreach($pt_ids as $row2) {
                    $pt_ids_array[$j] = $row2['parent_id'];
                    $j++;
                }
                $this->db->where_in('parent_id', $pt_ids_array);
                $parents = $this->db->get('parent')->result_array();
                foreach ($parents as $row):
                    ?>

                    <option value="parent-<?php echo $row['parent_id']; ?>">
                        - <?php echo $row['name']; ?></option>

                <?php endforeach; ?>
            </optgroup>
        </select>
    </div>


    <div class="compose-message-editor">
        <textarea row="5" class="form-control wysihtml5" data-stylesheet-url="<?php echo base_url('assets/css/wysihtml5-color.css');?>"
            name="message" placeholder="<?php echo get_phrase('write_your_message'); ?>"
            id="sample_wysiwyg" required></textarea>
    </div>

    <br>
    <!-- File adding module -->
    <div class="">
      <input type="file" class="form-control file2 inline btn btn-info" name="attached_file_on_messaging" accept=".pdf, .doc, .jpg, .jpeg, .png" data-label="<i class='entypo-upload'></i> Browse" />
    </div>
    <!-- end -->

    <hr>
    <button type="submit" class="btn btn-success btn-block pull-right">
        <?php echo get_phrase('send_message'); ?>
    </button>
</form>

</div>

<script>
$('#message_form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Sending message...', 'loading');
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
            showAjaxModal_alert(data.message, 'success');
            setTimeout(() => window.location.href = data.redirect, 2000);
        } else {
            showAjaxModal_alert(data.message || 'Operation failed', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});
</script>
