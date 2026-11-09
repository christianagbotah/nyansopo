<div class="row">
    <div class="col-lg-8 col-lg-offset-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-building"></i> 
                    <?php echo $form_action === 'add' ? get_phrase('add_location') : get_phrase('edit_location'); ?>
                </div>
            </div>
            <div class="panel-body">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('location_name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="location_name" class="form-control" required
                                       value="<?php echo $location ? $location->location_name : ''; ?>"
                                       placeholder="<?php echo get_phrase('e_g_main_campus'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('device_id'); ?></label>
                                <div class="input-group">
                                    <input type="text" name="device_id" class="form-control" id="device_id"
                                           value="<?php echo $location ? $location->device_id : ''; ?>"
                                           placeholder="<?php echo get_phrase('auto_generated'); ?>"
                                           <?php echo $location ? 'readonly' : ''; ?>>
                                    <?php if (!$location): ?>
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" onclick="generateDeviceId()">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo get_phrase('unique_identifier_for_this_location'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><?php echo get_phrase('api_endpoint'); ?></label>
                                <input type="url" name="api_endpoint" class="form-control"
                                       value="<?php echo $location ? $location->api_endpoint : ''; ?>"
                                       placeholder="https://branch-school.example.com">
                                <small class="text-muted"><?php echo get_phrase('url_for_remote_sync_operations'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('contact_email'); ?></label>
                                <input type="email" name="contact_email" class="form-control"
                                       value="<?php echo $location ? $location->contact_email : ''; ?>"
                                       placeholder="admin@branch-school.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('contact_phone'); ?></label>
                                <input type="text" name="contact_phone" class="form-control"
                                       value="<?php echo $location ? $location->contact_phone : ''; ?>"
                                       placeholder="+1 234 567 890">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('timezone'); ?></label>
                                <select name="timezone" class="form-control select2">
                                    <?php
                                    $timezones = [
                                        'UTC' => 'UTC',
                                        'Africa/Accra' => 'Africa/Accra (GMT)',
                                        'Africa/Lagos' => 'Africa/Lagos (WAT)',
                                        'America/New_York' => 'America/New_York (EST)',
                                        'America/Los_Angeles' => 'America/Los_Angeles (PST)',
                                        'Europe/London' => 'Europe/London (GMT)',
                                        'Europe/Paris' => 'Europe/Paris (CET)',
                                        'Asia/Dubai' => 'Asia/Dubai (GST)',
                                        'Asia/Singapore' => 'Asia/Singapore (SGT)',
                                        'Asia/Tokyo' => 'Asia/Tokyo (JST)'
                                    ];
                                    $current_tz = $location ? $location->timezone : 'UTC';
                                    foreach ($timezones as $tz => $label):
                                    ?>
                                        <option value="<?php echo $tz; ?>" <?php echo ($current_tz === $tz) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('priority'); ?></label>
                                <input type="number" name="priority" class="form-control"
                                       value="<?php echo $location ? $location->priority : 0; ?>"
                                       min="0" max="100">
                                <small class="text-muted"><?php echo get_phrase('higher_priority_syncs_first'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><?php echo get_phrase('description'); ?></label>
                                <textarea name="description" class="form-control" rows="3"
                                          placeholder="<?php echo get_phrase('notes_about_this_location'); ?>"><?php echo $location ? $location->description : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo get_phrase('save_location'); ?>
                            </button>
                            <a href="<?php echo site_url('locations'); ?>" class="btn btn-default">
                                <i class="fa fa-times"></i> <?php echo get_phrase('cancel'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function generateDeviceId() {
    $.ajax({
        url: '<?php echo site_url("locations/generate_device_id"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#device_id').val(response.device_id);
        }
    });
}
</script>
