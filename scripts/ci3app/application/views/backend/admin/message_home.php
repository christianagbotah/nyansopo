<?php $theme_color = get_settings('theme_color') ?: '#667eea'; ?>
<div class="msg-body-header" style="background: <?php echo $theme_color; ?>;">
    <h3><i class="fa fa-inbox"></i> <?php echo get_phrase('messages'); ?></h3>
    <p><?php echo get_phrase('select_conversation_to_start'); ?></p>
</div>

<div class="msg-content">
    <div class="msg-empty">
        <i class="fa fa-comments"></i>
        <div class="msg-empty-text"><?php echo get_phrase('select_message_to_read'); ?></div>
        <p style="margin-top: 8px; font-size: 14px;"><?php echo get_phrase('choose_conversation_from_sidebar'); ?></p>
    </div>
</div>
