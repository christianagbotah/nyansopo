<?php
$theme_color = get_settings('theme_color') ?: '#667eea';
$messages = $this->db->get_where('message', array('message_thread_code' => $current_message_thread_code))->result_array();
$current_user = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');

// Get conversation partner info
$thread = $this->db->get_where('message_thread', array('message_thread_code' => $current_message_thread_code))->row();
if($thread->sender == $current_user) {
    $partner = explode('-', $thread->reciever);
} else {
    $partner = explode('-', $thread->sender);
}
$partner_type = $partner[0];
$partner_id = $partner[1];
$partner_data = $this->db->get_where($partner_type, array($partner_type . '_id' => $partner_id))->row();
?>

<div class="msg-body-header" style="background: <?php echo $theme_color; ?>;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <img src="<?php echo $this->crud_model->get_image_url($partner_type, $partner_id); ?>" 
             style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid white;">
        <div>
            <h3 style="margin: 0;"><?php echo $partner_data->name; ?></h3>
            <p style="margin: 0; font-size: 13px; opacity: 0.9;">
                <i class="fa fa-circle" style="font-size: 8px;"></i> <?php echo ucfirst($partner_type); ?>
            </p>
        </div>
    </div>
</div>

<div class="msg-content" id="msgContent">
    <?php foreach ($messages as $row):
        $sender = explode('-', $row['sender']);
        $sender_account_type = $sender[0];
        $sender_id = $sender[1];
        $sender_data = $this->db->get_where($sender_account_type, array($sender_account_type . '_id' => $sender_id))->row();
        $is_sent = ($this->session->userdata($sender_account_type.'_id') == $sender_id);
        ?>
        
        <div class="msg-bubble <?php echo $is_sent ? 'sent' : ''; ?>">
            <img src="<?php echo $this->crud_model->get_image_url($sender_account_type, $sender_id); ?>" 
                 class="msg-avatar" alt="<?php echo $sender_data->name; ?>">
            
            <div class="msg-bubble-content">
                <div class="msg-bubble-header">
                    <span class="msg-sender-name"><?php echo $sender_data->name; ?></span>
                    <span class="msg-timestamp">
                        <i class="fa fa-clock"></i> <?php echo date("d M, Y - H:i", $row['timestamp']); ?>
                    </span>
                </div>
                
                <div class="msg-text">
                    <?php echo nl2br($row['message']); ?>
                </div>
                
                <?php if ($row['attached_file_name'] != ''): ?>
                    <a href="<?php echo base_url('uploads/private_messaging_attached_file/'.$row['attached_file_name']); ?>" 
                       target="_blank" download class="msg-attachment">
                        <i class="fa fa-paperclip"></i>
                        <span><?php echo $row['attached_file_name']; ?></span>
                        <i class="fa fa-download"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="msg-compose-area">
    <?php echo form_open(site_url('admin/message/send_reply/'.$current_message_thread_code), array('id' => 'msgReplyForm', 'enctype' => 'multipart/form-data')); ?>
    
    <div class="msg-compose-editor">
        <textarea name="message" required placeholder="<?php echo get_phrase('type_your_reply'); ?>..."></textarea>
    </div>
    
    <div class="msg-compose-actions">
        <div class="msg-file-input">
            <input type="file" class="form-control" name="attached_file_on_messaging" 
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                   style="padding: 8px; border: 2px dashed #e5e7eb; border-radius: 8px; font-size: 13px;">
        </div>
        <button type="submit" class="btn-send">
            <i class="fa fa-paper-plane"></i> <?php echo get_phrase('send'); ?>
        </button>
    </div>
    
    </form>
</div>

<script>
// Auto-scroll to bottom on load
$(document).ready(function() {
    const msgContent = document.getElementById('msgContent');
    msgContent.scrollTop = msgContent.scrollHeight;
});

$('#msgReplyForm').submit(function(e) {
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
        showAjaxModal_alert('<?php echo get_phrase("message_sent_successfully"); ?>', 'success', false);
        setTimeout(() => location.reload(), 1500);
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
    });
});
</script>
