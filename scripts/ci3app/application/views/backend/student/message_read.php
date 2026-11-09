
<?php
$messages = $this->db->get_where('message', array('message_thread_code' => $current_message_thread_code))->result_array(); 


foreach ($messages as $row):

    $sender = explode('-', $row['sender']);
    $sender_account_type = $sender[0];
    $sender_id = $sender[1];

    $b_color = '';
    $colors = '';
    $m_text_b = '#f9f9f9';
    if($this->session->userdata($sender_account_type.'_id') == $sender_id) {
        $b_color = '#564949';
        $colors = '#ffffff';
        $m_text_b = '#cfd4f9';
    }
    ?>


<div  id="message_<?php echo $row['message_id']; ?>" >
    <div class="mail-info" style="background-color: <?php echo $b_color; ?>">

        <div class="mail-sender" style="padding:7px;">

            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <img src="<?php echo $this->crud_model->get_image_url($sender_account_type, $sender_id); ?>" class="img-circle" width="30">
                <span style="color: <?php echo $colors; ?>"><?php echo $this->db->get_where($sender_account_type, array($sender_account_type . '_id' => $sender_id))->row()->name; ?></span>
            </a>

        </div>

        <div class="mail-date" style="padding:7px; color: <?php echo $colors; ?>">
            <?php echo date("d M, Y - H:i:s", $row['timestamp']); ?>
        </div>

    </div>

    <div class="mail-text" style="background-color: <?php echo $m_text_b; ?>">
        <p> <?php echo $row['message']; ?></p>
        <?php if ($row['attached_file_name'] != ''):?>
          <p style="text-align: right;">
            <a href="<?php echo base_url('uploads/private_messaging_attached_file/'.$row['attached_file_name']);?>" target="_blank" download style="color: #2196F3;">
            <i class="entypo-download" style="color: #757575"></i> <?php echo $row['attached_file_name']; ?>
          </a>
          </p>
        <?php endif; ?>
        <!--<a href="javascript:;" onclick="delete_message('message_<?php echo $row['message_id']; ?>')" title="Delete this message"><i class="entypo-trash pull-right"></i></a> --//-->
    </div>
</div>

<?php endforeach; ?>

<?php echo form_open(site_url('student/message/send_reply/'.$current_message_thread_code)  , array('enctype' => 'multipart/form-data')); ?>
<div class="mail-reply">
    <div class="compose-message-editor">
        <textarea row="5" class="form-control wysihtml5" data-stylesheet-url="<?php echo base_url('assets/css/wysihtml5-color.css');?>" name="message"
                  placeholder="<?php echo get_phrase('reply_message'); ?>" id="sample_wysiwyg" required></textarea>
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
    <br><br>
</div>
</form>

<script type="text/javascript">


    //delete message for the current user only
    function delete_message(message_id) {
        //show confirm box
        showCustomConfirm('Do you really want to delete this message?', function() {
            $('#' + message_id).css('display', 'none');

            $.ajax({
                url: '<?php echo site_url('admin/deleted_messages_id/') ?>' + message_id,
                success: function (response) {
                   // alert(response);
                }
            })
        });
    }
</script>