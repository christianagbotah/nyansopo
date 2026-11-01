<?php $theme_color = get_settings('theme_color') ?: '#667eea'; ?>
<div class="msg-body-header" style="background: <?php echo $theme_color; ?>;">
    <h3><i class="fa fa-sync"></i> <?php echo get_phrase('sync'); ?></h3>
    <p><?php echo get_phrase('synchronization_status'); ?></p>
</div>

<div class="msg-content">
    <div class="msg-empty">
        <i class="fa fa-sync-alt"></i>
        <div class="msg-empty-text"><?php echo get_phrase('sync_feature'); ?></div>
        <p style="margin-top: 8px; font-size: 14px;"><?php echo get_phrase('sync_feature_coming_soon'); ?></p>
    </div>
</div>
