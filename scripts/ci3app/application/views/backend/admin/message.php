<?php
$theme_color = get_settings('theme_color') ?: '#667eea';
$sms_sender = get_settings('hubtel_sender') ?: get_settings('system_name');
// Calculate color variations inline
$hex = str_replace('#', '', $theme_color);
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$theme_dark = '#' . str_pad(dechex(max(0, $r - 20)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(max(0, $g - 20)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(max(0, $b - 20)), 2, '0', STR_PAD_LEFT);
$theme_light = '#' . str_pad(dechex(min(255, $r + 20)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(min(255, $g + 20)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(min(255, $b + 20)), 2, '0', STR_PAD_LEFT);
?>
<style>
:root {
    --theme-color: <?php echo $theme_color; ?>;
    --theme-color-dark: <?php echo $theme_dark; ?>;
    --theme-color-light: <?php echo $theme_light; ?>;
    --theme-color-alpha-10: rgba(<?php echo "$r, $g, $b, 0.1"; ?>);
    --theme-color-alpha-20: rgba(<?php echo "$r, $g, $b, 0.2"; ?>);
    --theme-color-alpha-30: rgba(<?php echo "$r, $g, $b, 0.3"; ?>);
    --theme-color-alpha-40: rgba(<?php echo "$r, $g, $b, 0.4"; ?>);
}
.messaging-wrapper { background: #f8f9fa; padding: 24px; min-height: calc(100vh - 120px); }
.msg-tabs { display: flex; gap: 12px; margin-bottom: 24px; background: white; padding: 8px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.msg-tab { flex: 1; padding: 16px 24px; border: none; background: transparent; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; transition: all 0.3s; color: #6b7280; display: flex; align-items: center; justify-content: center; gap: 8px; }
.msg-tab:hover { background: #f3f4f6; }
.msg-tab.active { background: var(--theme-color); color: white; box-shadow: 0 4px 12px var(--theme-color-alpha-30); }
.msg-tab.active:first-child { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.msg-tab i { font-size: 20px; }
.msg-tab-content { display: none; }
.msg-tab-content.active { display: block; animation: fadeIn 0.3s; }
.messaging-container { display: flex; height: calc(100vh - 240px); background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.msg-sidebar { width: 320px; background: white; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; }
.msg-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); background: var(--theme-color); color: white; }
.msg-header h2 { margin: 0 0 16px 0; font-size: 24px; font-weight: 700; color: white; }
.msg-actions { display: flex; gap: 8px; }
.btn-compose { flex: 1; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-compose:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); background: rgba(255,255,255,0.3); }
.msg-search { padding: 12px 20px; border-bottom: 1px solid #e5e7eb; }
.msg-search input { width: 100%; padding: 10px 16px 10px 40px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
.msg-search input:focus { outline: none; border-color: #667eea; }
.msg-search-icon { position: absolute; left: 32px; top: 22px; color: #9ca3af; }
.msg-list { flex: 1; overflow-y: auto; }
.msg-thread { padding: 16px 20px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: all 0.2s; position: relative; }
.msg-thread:hover { background: #f9fafb; }
.msg-thread.active { background: var(--theme-color-alpha-10); border-left: 4px solid var(--theme-color); }
.msg-thread-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.msg-thread-name { font-weight: 600; color: #1a202c; font-size: 15px; }
.msg-thread-badge { background: var(--theme-color); color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.msg-thread-time { font-size: 12px; color: #9ca3af; }
.msg-unread { background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; margin-left: 8px; }
.msg-delete { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); opacity: 0; transition: all 0.2s; color: #ef4444; padding: 8px; }
.msg-thread:hover .msg-delete { opacity: 1; }
.msg-body { flex: 1; display: flex; flex-direction: column; background: white; }
.msg-body-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: var(--theme-color); color: white; }
.msg-body-header h3 { margin: 0; font-size: 20px; font-weight: 600; }
.msg-body-header p { margin: 4px 0 0 0; font-size: 14px; opacity: 0.9; }
.msg-content { flex: 1; overflow-y: auto; padding: 24px; background: #f9fafb; }
.msg-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #9ca3af; }
.msg-empty i { font-size: 80px; margin-bottom: 20px; opacity: 0.3; }
.msg-empty-text { font-size: 18px; font-weight: 500; }
.msg-bubble { margin-bottom: 20px; display: flex; gap: 12px; animation: fadeIn 0.3s; }
.msg-bubble.sent { flex-direction: row-reverse; }
.msg-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.msg-bubble-content { max-width: 60%; }
.msg-bubble-header { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.msg-bubble.sent .msg-bubble-header { flex-direction: row-reverse; }
.msg-sender-name { font-weight: 600; font-size: 14px; color: #374151; }
.msg-timestamp { font-size: 12px; color: #9ca3af; }
.msg-text { background: white; padding: 12px 16px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); color: #1a202c; line-height: 1.5; }
.msg-bubble.sent .msg-text { background: var(--theme-color); color: white; }
.msg-attachment { margin-top: 8px; padding: 8px 12px; background: #f3f4f6; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #4b5563; }
.msg-attachment i { color: var(--theme-color); }
.msg-compose-area { padding: 20px 24px; border-top: 1px solid #e5e7eb; background: white; }
.msg-compose-editor { margin-bottom: 12px; }
.msg-compose-editor textarea { width: 100%; min-height: 100px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical; transition: all 0.3s; }
.msg-compose-editor textarea:focus { outline: none; border-color: var(--theme-color); }
.msg-compose-actions { display: flex; gap: 12px; align-items: center; }
.msg-file-input { flex: 1; }
.btn-send { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-send:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.sms-container { background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.sms-header { text-align: center; margin-bottom: 32px; }
.sms-header h2 { font-size: 28px; font-weight: 700; color: #1a202c; margin: 0 0 8px 0; }
.sms-header p { color: #6b7280; font-size: 15px; }
.sms-type-selector { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; }
.sms-type-card { padding: 24px; border: 2px solid #e5e7eb; border-radius: 12px; cursor: pointer; transition: all 0.3s; text-align: center; }
.sms-type-card:hover { border-color: #10b981; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
.sms-type-card.selected { border-color: #10b981; background: rgba(16, 185, 129, 0.1); }
.sms-type-card i { font-size: 40px; color: #10b981; margin-bottom: 12px; }
.sms-type-card h3 { margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #1a202c; }
.sms-type-card p { margin: 0; font-size: 13px; color: #6b7280; }
.sms-form-select { width: 100%; border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 15px; transition: all 0.3s; height: 42px; }
.sms-form-select:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }
.sms-form-input { width: 100%; padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; transition: all 0.3s; }
.sms-form-input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }
.sms-form-textarea { width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; resize: vertical; transition: all 0.3s; line-height: 1.5; }
.sms-form-textarea:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }
.tags-input-container { min-height: 42px; border: 2px solid #e5e7eb; border-radius: 10px; padding: 6px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; cursor: text; transition: all 0.3s; }
.tags-input-container:focus-within { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
.tag-item { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 6px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; animation: tagSlide 0.2s; }
.tag-remove { cursor: pointer; font-weight: bold; opacity: 0.8; transition: opacity 0.2s; }
.tag-remove:hover { opacity: 1; }
.tags-input { border: none; outline: none; flex: 1; min-width: 120px; padding: 6px; font-size: 15px; }
@keyframes tagSlide { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
.phone-simulator { width: 280px; height: 560px; background: #1a1a1a; border-radius: 36px; padding: 10px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative; }
.phone-screen { width: 100%; height: 100%; background: #f5f5f5; border-radius: 26px; overflow: hidden; display: flex; flex-direction: column; }
.phone-notch { height: 26px; background: #1a1a1a; border-radius: 0 0 18px 18px; margin: 0 auto; width: 130px; display: flex; align-items: center; justify-content: center; }
.phone-camera { width: 7px; height: 7px; background: #333; border-radius: 50%; }
.phone-status { padding: 6px 14px; display: flex; justify-content: space-between; font-size: 10px; color: #666; }
.phone-header { background: white; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 10px; }
.phone-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--theme-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; }
.phone-contact { flex: 1; }
.phone-contact-name { font-weight: 600; font-size: 13px; color: #1a202c; }
.phone-contact-number { font-size: 10px; color: #9ca3af; }
.phone-messages { flex: 1; padding: 14px; overflow-y: auto; background: #e5ddd5; }
.phone-message { background: white; padding: 9px 12px; border-radius: 7px; max-width: 80%; margin-bottom: 7px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); animation: messageSlide 0.3s; }
.phone-message-text { font-size: 12px; line-height: 1.4; color: #1a202c; word-wrap: break-word; }
.phone-message-time { font-size: 9px; color: #9ca3af; margin-top: 3px; text-align: right; }
@keyframes messageSlide { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 768px) {
    .messaging-wrapper { padding: 12px; }
    .msg-tabs { flex-direction: column; }
    .messaging-container { flex-direction: column; height: auto; }
    .msg-sidebar { width: 100%; border-right: none; border-bottom: 1px solid #e5e7eb; }
    .msg-body { min-height: 500px; }
    .sms-container { padding: 20px; }
}
</style>

<div class="messaging-wrapper">
    <!-- Tabs -->
    <div class="msg-tabs">
        <button class="msg-tab active" onclick="switchTab('sms')">
            <i class="fa fa-mobile-alt"></i>
            <span><?php echo get_phrase('sms_messages'); ?></span>
        </button>
        <button class="msg-tab" onclick="switchTab('inapp')">
            <i class="fa fa-comments"></i>
            <span><?php echo get_phrase('in_app_messages'); ?></span>
        </button>
    </div>

    <!-- SMS Tab -->
    <div class="msg-tab-content active" id="sms-tab">
        <div class="sms-container">
            <div class="sms-header" style="position: relative;">
                <h2><i class="fa fa-mobile-alt"></i> <?php echo get_phrase('send_sms'); ?></h2>
                <p><?php echo get_phrase('send_sms_to_students_teachers_parents'); ?></p>
                <button onclick="sendBillReminder()" style="position: absolute; right: 0; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-50%) translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(245, 158, 11, 0.4)'" onmouseout="this.style.transform='translateY(-50%)'; this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.3)'">
                    <i class="fa fa-bell"></i> <strong>Send Bill Reminder</strong>
                </button>
            </div>

            <div class="sms-type-selector">
                <div class="sms-type-card" onclick="showSMSForm('individual')">
                    <i class="fa fa-user"></i>
                    <h3><?php echo get_phrase('individual_sms'); ?></h3>
                    <p><?php echo get_phrase('send_to_specific_person'); ?></p>
                </div>
                <div class="sms-type-card" onclick="showSMSForm('bulk')">
                    <i class="fa fa-users"></i>
                    <h3><?php echo get_phrase('bulk_sms'); ?></h3>
                    <p><?php echo get_phrase('send_to_groups'); ?></p>
                </div>
                <div class="sms-type-card" onclick="showSMSForm('custom')">
                    <i class="fa fa-phone"></i>
                    <h3><?php echo get_phrase('custom_numbers'); ?></h3>
                    <p><?php echo get_phrase('enter_phone_numbers'); ?></p>
                </div>
            </div>

            <!-- SMS Form Container -->
            <div id="smsFormContainer" style="display: none;"></div>

            <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%); padding: 24px; border-radius: 12px; border-left: 4px solid #10b981;">
                <h4 style="margin: 0 0 12px 0; color: #1a202c; font-weight: 600;">
                    <i class="fa fa-info-circle" style="color: #10b981;"></i> <?php echo get_phrase('sms_information'); ?>
                </h4>
                <ul style="margin: 0; padding-left: 20px; color: #6b7280; line-height: 1.8;">
                    <li><?php echo get_phrase('sms_sent_directly_to_phone'); ?></li>
                    <li><?php echo get_phrase('charges_may_apply'); ?></li>
                    <li><?php echo get_phrase('160_characters_per_sms'); ?></li>
                    <li><?php echo get_phrase('delivery_status_available'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- In-App Messages Tab -->
    <div class="msg-tab-content" id="inapp-tab">
        <div class="messaging-container">
            <!-- Sidebar -->
            <div class="msg-sidebar">
                <div class="msg-header">
                    <h2><i class="fa fa-comments"></i> <?php echo get_phrase('conversations'); ?></h2>
                    <div class="msg-actions">
                        <button class="btn-compose" onclick="navigation('<?php echo site_url('admin/message/message_new'); ?>')">
                            <i class="fa fa-plus-circle"></i> <?php echo get_phrase('new_message'); ?>
                        </button>
                    </div>
                </div>

                <div class="msg-search" style="position: relative;">
                    <i class="fa fa-search msg-search-icon"></i>
                    <input type="text" id="msgSearch" placeholder="<?php echo get_phrase('search_messages'); ?>..." onkeyup="filterMessages()">
                </div>

                <div class="msg-list">
                    <?php
                    $current_user = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');
                    $this->db->where('sender', $current_user);
                    $this->db->or_where('reciever', $current_user);
                    $this->db->order_by('last_message_timestamp', 'DESC');
                    $message_threads = $this->db->get('message_thread')->result_array();
                    
                    if(empty($message_threads)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #9ca3af;">
                            <i class="fa fa-inbox" style="font-size: 48px; opacity: 0.3; margin-bottom: 12px;"></i>
                            <p><?php echo get_phrase('no_messages_yet'); ?></p>
                        </div>
                    <?php else:
                        foreach ($message_threads as $row):
                            if ($row['sender'] == $current_user)
                                $user_to_show = explode('-', $row['reciever']);
                            else
                                $user_to_show = explode('-', $row['sender']);

                            $user_to_show_type = $user_to_show[0];
                            $user_to_show_id = $user_to_show[1];
                            $user_data = $this->db->get_where($user_to_show_type, array($user_to_show_type . '_id' => $user_to_show_id))->row();
                            $unread_message_number = $this->crud_model->count_unread_message_of_thread($row['message_thread_code']);
                            $last_message_time = isset($row['last_message_timestamp']) ? $row['last_message_timestamp'] : time();
                            ?>
                            <div class="msg-thread <?php if (isset($current_message_thread_code) && $current_message_thread_code == $row['message_thread_code']) echo 'active'; ?>" 
                                 data-name="<?php echo strtolower($user_data->name); ?>"
                                 onclick="navigation('<?php echo site_url('admin/message/message_read/'.$row['message_thread_code']); ?>')">
                                <div class="msg-thread-header">
                                    <div>
                                        <span class="msg-thread-name"><?php echo $user_data->name; ?></span>
                                        <?php if ($unread_message_number > 0): ?>
                                            <span class="msg-unread"><?php echo $unread_message_number; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="msg-thread-badge"><?php echo ucfirst($user_to_show_type); ?></span>
                                </div>
                                <div class="msg-thread-time">
                                    <i class="fa fa-clock"></i> <?php echo date('d M, H:i', $last_message_time); ?>
                                </div>
                                <button class="msg-delete" onclick="event.stopPropagation(); deleteMessage('<?php echo $row['message_thread_code'];?>')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>

            <!-- Message Body -->
            <div class="msg-body">
                <?php 
                $inner_page_file = $message_inner_page_name . '.php';
                if (file_exists($inner_page_file)) {
                    include $inner_page_file;
                } else {
                    include 'message_home.php';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.msg-tab').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.msg-tab').classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.msg-tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + '-tab').classList.add('active');
}

function filterMessages() {
    const search = document.getElementById('msgSearch').value.toLowerCase();
    const threads = document.querySelectorAll('.msg-thread');
    
    threads.forEach(thread => {
        const name = thread.getAttribute('data-name');
        if(name.includes(search)) {
            thread.style.display = 'block';
        } else {
            thread.style.display = 'none';
        }
    });
}

function deleteMessage(message_thread_code) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_delete"); ?>',
        '<?php echo get_phrase("delete_message_thread_confirm"); ?>',
        function() {
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase("deleting"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/message/delete/');?>' + message_thread_code,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.message === 'done') {
                    showAjaxModal_alert('<?php echo get_phrase("message_deleted_successfully"); ?>', 'success');
                    setTimeout(() => navigation('<?php echo site_url('admin/message'); ?>'), 2000);
                } else {
                    showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("delete"); ?>',
        'danger'
    );
}

function showSMSForm(type) {
    const container = document.getElementById('smsFormContainer');
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Remove selected class from all cards
    document.querySelectorAll('.sms-type-card').forEach(card => card.classList.remove('selected'));
    // Add selected class to clicked card
    event.target.closest('.sms-type-card').classList.add('selected');
    
    let formHTML = `<div style="display: grid; grid-template-columns: 1fr 280px; gap: 24px; margin-top: 24px;"><div style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); animation: fadeIn 0.3s;"><div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;"><h3 style="margin: 0; color: #1a202c; font-weight: 700;"><i class="fa fa-${type === 'individual' ? 'user' : type === 'bulk' ? 'users' : 'phone'}"></i> ${type === 'individual' ? '<?php echo get_phrase("individual_sms"); ?>' : type === 'bulk' ? '<?php echo get_phrase("bulk_sms"); ?>' : '<?php echo get_phrase("custom_numbers"); ?>'}</h3><button onclick="document.getElementById('smsFormContainer').style.display='none'" style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;"><i class="fa fa-times"></i> <?php echo get_phrase("close"); ?></button></div><form id="smsForm">`;
    
    if(type === 'individual') {
        formHTML += `<div style="margin-bottom: 20px;"><label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;"><i class="fa fa-users"></i> Select Recipients:</label><div class="tags-input-container" id="recipientsContainer" onclick="document.getElementById('recipientsInput').focus()"><input type="text" id="recipientsInput" class="tags-input" placeholder="Type to search and select..."></div><input type="hidden" name="reciever[]" id="recipientsHidden"><div style="margin-top: 8px; max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; display: none;" id="recipientsList"></div></div><input type="hidden" name="bulk" value="1">`;
    } else if(type === 'bulk') {
        formHTML += `<div style="margin-bottom: 20px;"><label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;"><i class="fa fa-layer-group"></i> <?php echo get_phrase("select_group"); ?>:</label><select class="form-control sms-form-select" name="bulk" required><option value="">Select Group</option><option value="6">All Active Admins</option><option value="3">All Active Students</option><option value="2">All Active Teachers</option><option value="4">All Active Parents</option></select></div>`;
    } else {
        formHTML += `<div style="margin-bottom: 20px;"><label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;"><i class="fa fa-phone"></i> <?php echo get_phrase("phone_numbers"); ?>:</label><div class="tags-input-container" id="phoneContainer" onclick="document.getElementById('phoneInput').focus()"><input type="text" id="phoneInput" class="tags-input" placeholder="Enter phone number and press Enter..."></div><input type="hidden" name="phone[]" id="phoneHidden"><small style="color: #6b7280; margin-top: 4px; display: block;">Press Enter or comma to add each number</small></div><input type="hidden" name="bulk" value="5">`;
    }
    
    formHTML += `<div style="margin-bottom: 20px;"><label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;"><i class="fa fa-comment"></i> <?php echo get_phrase("message"); ?>:</label><textarea id="smsMessage" name="message" required rows="5" class="sms-form-textarea" placeholder="Type your message..." onkeyup="updateSMSPreview(this)"></textarea><div style="display: flex; justify-content: space-between; margin-top: 4px;"><small style="color: #6b7280;"><span id="charCount">0</span>/160 characters</small><small style="color: #6b7280;"><span id="smsCount">1</span> SMS</small></div></div><button type="submit" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer;"><i class="fa fa-paper-plane"></i> <?php echo get_phrase("send_sms"); ?></button></form></div><div><div class="phone-simulator"><div class="phone-screen"><div class="phone-notch"><div class="phone-camera"></div></div><div class="phone-status"><span>9:41</span><span><i class="fa fa-signal"></i> <i class="fa fa-wifi"></i> <i class="fa fa-battery-full"></i></span></div><div class="phone-header"><div class="phone-avatar"><?php echo strtoupper(substr($sms_sender, 0, 1)); ?></div><div class="phone-contact"><div class="phone-contact-name"><?php echo $sms_sender; ?></div><div class="phone-contact-number">SMS Preview</div></div></div><div class="phone-messages" id="phoneMessages"><div style="text-align: center; color: #9ca3af; font-size: 12px; padding: 20px;">Type your message to see preview</div></div></div></div></div></div>`;
    
    container.innerHTML = formHTML;
    
    if(type === 'individual') {
        initRecipientsTags();
    } else if(type === 'custom') {
        initPhoneTags();
    }
    
    $('#smsForm').submit(function(e) {
        e.preventDefault();
        showAjaxModal_alert('Sending SMS...', 'loading');
        var formData = $(this).serialize();
        formData += '&<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>';
        $.ajax({
            url: '<?php echo site_url("admin/message/sms_send/sms_submitted"); ?>',
            type: 'POST',
            data: formData,
            dataType: 'json'
        }).done(function(response) {
            // Check if SMS send was successful (doesn't start with 'failed')
            if(response.send_sms && !response.send_sms.toLowerCase().startsWith('failed')) {
                showAjaxModal_alert(response.send_sms || 'SMS sent successfully', 'success', false);
                document.getElementById('smsFormContainer').style.display = 'none';
            } else {
                // Display the full error message including balance details
                var errorMsg = response.send_sms || 'SMS sending failed';
                // Make the error message more readable by replacing ' - ' with line breaks
                errorMsg = errorMsg.replace(/ - /g, '<br>');
                showAjaxModal_alert(errorMsg, 'error');
            }
        }).fail(function(err) {
            showAjaxModal_alert('An error occurred: ' + err.responseText, 'error');
        });
    });
}

function updateSMSPreview(textarea) {
    const message = textarea.value;
    const count = message.length;
    const smsCount = Math.ceil(count / 160) || 1;
    
    document.getElementById('charCount').textContent = count;
    document.getElementById('smsCount').textContent = smsCount;
    
    const phoneMessages = document.getElementById('phoneMessages');
    if(message.trim()) {
        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        phoneMessages.innerHTML = `<div class="phone-message"><div class="phone-message-text">${message.replace(/\n/g, '<br>')}</div><div class="phone-message-time">${time}</div></div>`;
    } else {
        phoneMessages.innerHTML = '<div style="text-align: center; color: #9ca3af; font-size: 12px; padding: 20px;">Type your message to see preview</div>';
    }
}

const recipientsData = <?php
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$all_recipients = [];
$students = $this->db->get_where('enroll', array('year' => $running_year, 'term' => $running_term, 'mute' => '0'))->result_array();
foreach ($students as $row) {
    $student = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
    if($student) $all_recipients[] = ['value' => 'student-'.$student->student_id, 'label' => $student->name, 'type' => 'Student'];
}
$teachers = $this->db->get_where('teacher', array('block_limit' => '0'))->result_array();
foreach ($teachers as $row) {
    $all_recipients[] = ['value' => 'teacher-'.$row['teacher_id'], 'label' => $row['name'], 'type' => 'Teacher'];
}
$st_ids = $this->db->select('student_id')->where('year', $running_year)->where('term', $running_term)->where('mute', '0')->get('enroll')->result_array();
$st_ids_array = array_column($st_ids, 'student_id');
if(!empty($st_ids_array)) {
    $pt_ids = $this->db->select('parent_id')->where_in('student_id', $st_ids_array)->get('student')->result_array();
    $pt_ids_array = array_unique(array_column($pt_ids, 'parent_id'));
    if(!empty($pt_ids_array)) {
        $parents = $this->db->where_in('parent_id', $pt_ids_array)->get('parent')->result_array();
        foreach ($parents as $row) {
            $all_recipients[] = ['value' => 'parent-'.$row['parent_id'], 'label' => $row['name'], 'type' => 'Parent'];
        }
    }
}
echo json_encode($all_recipients);
?>;

let selectedRecipients = [];

function initRecipientsTags() {
    const input = document.getElementById('recipientsInput');
    const list = document.getElementById('recipientsList');
    
    input.addEventListener('input', function() {
        const search = this.value.toLowerCase();
        if(search.length < 2) {
            list.style.display = 'none';
            return;
        }
        const filtered = recipientsData.filter(r => 
            !selectedRecipients.includes(r.value) && 
            r.label.toLowerCase().includes(search)
        ).slice(0, 10);
        
        if(filtered.length > 0) {
            list.innerHTML = filtered.map(r => 
                `<div style="padding: 10px; cursor: pointer; border-bottom: 1px solid #f3f4f6; hover:background: #f9fafb;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'" onclick="addRecipient('${r.value}', '${r.label}', '${r.type}')">
                    <div style="font-weight: 600; font-size: 14px;">${r.label}</div>
                    <div style="font-size: 12px; color: #9ca3af;">${r.type}</div>
                </div>`
            ).join('');
            list.style.display = 'block';
        } else {
            list.style.display = 'none';
        }
    });
}

function addRecipient(value, label, type) {
    selectedRecipients.push(value);
    const container = document.getElementById('recipientsContainer');
    const input = document.getElementById('recipientsInput');
    const tag = document.createElement('div');
    tag.className = 'tag-item';
    tag.innerHTML = `<span>${label} <small>(${type})</small></span><span class="tag-remove" onclick="removeRecipient('${value}', this.parentElement)">×</span>`;
    container.insertBefore(tag, input);
    input.value = '';
    document.getElementById('recipientsList').style.display = 'none';
    document.getElementById('recipientsHidden').value = selectedRecipients.join(',');
}

function removeRecipient(value, element) {
    selectedRecipients = selectedRecipients.filter(v => v !== value);
    element.remove();
    document.getElementById('recipientsHidden').value = selectedRecipients.join(',');
}

let phoneNumbers = [];

function initPhoneTags() {
    const input = document.getElementById('phoneInput');
    
    input.addEventListener('keydown', function(e) {
        if(e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addPhoneNumber();
        }
    });
    
    input.addEventListener('blur', function() {
        if(this.value.trim()) addPhoneNumber();
    });
}

function addPhoneNumber() {
    const input = document.getElementById('phoneInput');
    const number = input.value.replace(/,/g, '').trim();
    
    if(number && !phoneNumbers.includes(number)) {
        phoneNumbers.push(number);
        const container = document.getElementById('phoneContainer');
        const tag = document.createElement('div');
        tag.className = 'tag-item';
        tag.innerHTML = `<span>${number}</span><span class="tag-remove" onclick="removePhoneNumber('${number}', this.parentElement)">×</span>`;
        container.insertBefore(tag, input);
        input.value = '';
        document.getElementById('phoneHidden').value = phoneNumbers.join(',');
    }
}

function removePhoneNumber(number, element) {
    phoneNumbers = phoneNumbers.filter(n => n !== number);
    element.remove();
    document.getElementById('phoneHidden').value = phoneNumbers.join(',');
}

function sendBillReminder() {
    showConfirmModal(
        'Send Bill Reminder',
        'This will preview SMS alerts for all parents with outstanding balances. Continue?',
        function() {
            window.open('<?php echo site_url("admin/send_bill_reminder"); ?>', '_blank');
        },
        'Preview',
        'warning'
    );
}
</script>
