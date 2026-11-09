<div class="pull-right" style="text-align: right; margin-top: -30px;">
  <a href="<?php echo site_url('student/group_message'); ?>" class="btn btn-blue"><i class="fa fa-comments" aria-hidden="true"></i> <?php echo get_phrase('group_message'); ?></a>
</div>
<hr />
<div class="mail-env">

    <!-- Sidebar -->
    <div class="mail-sidebar">

        <!-- compose new email button -->
        <div class="mail-sidebar-row">
            <a href="<?php echo site_url('student/message/message_new'); ?>" class="btn btn-success btn-icon btn-block">
                <?php echo get_phrase('new_message'); ?>
                <i class="entypo-pencil"></i>
            </a>
        </div>

        <!-- message user inbox list -->
        <ul class="mail-menu">

            <?php
            $current_user = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');

            $this->db->where('sender', $current_user);
            $this->db->or_where('reciever', $current_user);
            $message_threads = $this->db->get('message_thread')->result_array();
            foreach ($message_threads as $row):

                // defining the user to show
                if ($row['sender'] == $current_user)
                    $user_to_show = explode('-', $row['reciever']);
                if ($row['reciever'] == $current_user)
                    $user_to_show = explode('-', $row['sender']);

                $user_to_show_type = $user_to_show[0];
                $user_to_show_id = $user_to_show[1];
                $unread_message_number = $this->crud_model->count_unread_message_of_thread($row['message_thread_code']);
                ?>
                <li class="<?php if (isset($current_message_thread_code) && $current_message_thread_code == $row['message_thread_code']) echo 'active'; ?>">
                    <a href="<?php echo site_url('student/message/message_read/'.$row['message_thread_code']); ?>" class="col-sm-10 col-xs-10" style="padding:12px;">
                        <i class="entypo-dot"></i>

                        <?php echo $this->db->get_where($user_to_show_type, array($user_to_show_type . '_id' => $user_to_show_id))->row()->name; ?>

                        <span class="badge badge-default pull-right" style="color:#f1e2e2; background: black;"><?php echo $user_to_show_type; ?></span>

                        <?php if ($unread_message_number > 0): ?>
                            <span class="badge badge-secondary pull-right">
                                <?php echo $unread_message_number; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="javascript:void(0)" class="btn btn-xs col-sm-2 col-xs-2" onclick="message_delete_confirm('<?php echo $row['message_thread_code'];?>');"><i class="entypo-trash" style="color: red; margin-left: 5px;"></i></a>
                </li>
            <?php endforeach; ?>
        </ul>

    </div>

    <!-- Mail Body -->
    <div class="mail-body">
        <!-- message page body -->
        <?php include $message_inner_page_name . '.php'; ?>
    </div>

</div>

<script type="text/javascript">
  function message_delete_confirm(message_thread_code) {
        confirm_modal('<?php echo site_url('student/message/delete/');?>' + message_thread_code);
    }
</script>