<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<div class="bg-white rounded-lg shadow-sm">
    <div class="flex justify-between items-center p-6 border-b">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('messages');?></h2>
            <p class="text-gray-600"><?php echo get_phrase('communicate_with_staff_and_parents');?></p>
        </div>
        <a href="<?php echo site_url('teacher/group_message'); ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition flex items-center gap-2">
            <i class="fa fa-comments"></i> <?php echo get_phrase('group_message'); ?>
        </a>
    </div>

<div class="flex flex-col md:flex-row">

    <div class="w-full md:w-80 border-r bg-gray-50">
        <div class="p-4">
            <a href="<?php echo site_url('teacher/message/message_new'); ?>" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition flex items-center justify-center gap-2">
                <i class="entypo-plus"></i> <?php echo get_phrase('new_message'); ?>
            </a>
        </div>

        <div class="overflow-y-auto" style="max-height: 600px;">

            <?php
            $current_user = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');
            $this->db->where('sender', $current_user);
            $this->db->or_where('reciever', $current_user);
            $message_threads = $this->db->get('message_thread')->result_array();
            foreach ($message_threads as $row):
                if ($row['sender'] == $current_user)
                    $user_to_show = explode('-', $row['reciever']);
                if ($row['reciever'] == $current_user)
                    $user_to_show = explode('-', $row['sender']);
                $user_to_show_type = $user_to_show[0];
                $user_to_show_id = $user_to_show[1];
                $unread_message_number = $this->crud_model->count_unread_message_of_thread($row['message_thread_code']);
                $is_active = isset($current_message_thread_code) && $current_message_thread_code == $row['message_thread_code'];
            ?>
            <div class="border-b hover:bg-white transition <?php echo $is_active ? 'bg-white border-l-4 border-l-blue-600' : ''; ?>">
                <div class="flex items-center p-4 gap-3">
                    <a href="<?php echo site_url('teacher/message/message_read/'.$row['message_thread_code']); ?>" class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-900">
                                <?php echo $this->db->get_where($user_to_show_type, array($user_to_show_type . '_id' => $user_to_show_id))->row()->name; ?>
                            </span>
                            <?php if ($unread_message_number > 0): ?>
                                <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full"><?php echo $unread_message_number; ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs text-gray-500 uppercase"><?php echo $user_to_show_type; ?></span>
                    </a>
                    <button onclick="message_delete_confirm('<?php echo $row['message_thread_code'];?>');" class="text-red-500 hover:text-red-700 p-2">
                        <i class="entypo-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex-1 bg-white">
        <?php include $message_inner_page_name . '.php'; ?>
    </div>
</div>
</div>

<script>
function message_delete_confirm(message_thread_code) {
    confirm_modal('<?php echo site_url('teacher/message/delete/');?>' + message_thread_code);
}
</script>