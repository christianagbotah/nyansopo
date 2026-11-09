<?php
/**
 * Edit Sync Location View
 */
?>

<div class="row">
    <div class="col-md-12">
        
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="pull-right">
                    <a href="<?php echo site_url('admin/sync_locations'); ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back'); ?>
                    </a>
                </div>
                <h3 class="panel-title">
                    <i class="fa fa-edit"></i> <?php echo get_phrase('edit_location'); ?>
                </h3>
            </div>
            
            <div class="panel-body">
                <form action="<?php echo site_url('admin/sync_locations/update/' . $location->id); ?>" method="post" class="form-horizontal">
                    
                    <!-- Location Name -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('location_name'); ?> *</label>
                        <div class="col-sm-9">
                            <input type="text" name="location_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($location->location_name); ?>">
                        </div>
                    </div>
                    
                    <!-- Device ID (Read-only) -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('device_id'); ?></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" readonly 
                                   value="<?php echo htmlspecialchars($location->device_id); ?>">
                            <small class="text-muted">Device ID cannot be changed</small>
                        </div>
                    </div>
                    
                    <!-- API Endpoint -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('api_endpoint'); ?></label>
                        <div class="col-sm-9">
                            <input type="url" name="api_endpoint" class="form-control" 
                                   value="<?php echo htmlspecialchars($location->api_endpoint); ?>">
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('status'); ?></label>
                        <div class="col-sm-9">
                            <select name="status" class="form-control">
                                <option value="active" <?php echo $location->status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $location->status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $location->status == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Priority -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('priority'); ?></label>
                        <div class="col-sm-9">
                            <input type="number" name="priority" class="form-control" 
                                   value="<?php echo $location->priority; ?>" min="0" max="100">
                        </div>
                    </div>
                    
                    <!-- Sync Enabled -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('sync_enabled'); ?></label>
                        <div class="col-sm-9">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="sync_enabled" value="1" 
                                       <?php echo $location->sync_enabled ? 'checked' : ''; ?>> 
                                Enable synchronization for this location
                            </label>
                        </div>
                    </div>
                    
                    <!-- Contact Email -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('contact_email'); ?></label>
                        <div class="col-sm-9">
                            <input type="email" name="contact_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($location->contact_email); ?>">
                        </div>
                    </div>
                    
                    <!-- Contact Phone -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('contact_phone'); ?></label>
                        <div class="col-sm-9">
                            <input type="tel" name="contact_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($location->contact_phone); ?>">
                        </div>
                    </div>
                    
                    <!-- Timezone -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('timezone'); ?></label>
                        <div class="col-sm-9">
                            <select name="timezone" class="form-control">
                                <option value="UTC" <?php echo $location->timezone == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="Africa/Accra" <?php echo $location->timezone == 'Africa/Accra' ? 'selected' : ''; ?>>Africa/Accra (GMT)</option>
                                <option value="Africa/Lagos" <?php echo $location->timezone == 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (WAT)</option>
                                <option value="Africa/Nairobi" <?php echo $location->timezone == 'Africa/Nairobi' ? 'selected' : ''; ?>>Africa/Nairobi (EAT)</option>
                                <option value="America/New_York" <?php echo $location->timezone == 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                <option value="America/Chicago" <?php echo $location->timezone == 'America/Chicago' ? 'selected' : ''; ?>>America/Chicago (CST)</option>
                                <option value="America/Los_Angeles" <?php echo $location->timezone == 'America/Los_Angeles' ? 'selected' : ''; ?>>America/Los_Angeles (PST)</option>
                                <option value="Europe/London" <?php echo $location->timezone == 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                <option value="Asia/Dubai" <?php echo $location->timezone == 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai (GST)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('description'); ?></label>
                        <div class="col-sm-9">
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($location->description); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo get_phrase('update_location'); ?>
                            </button>
                            <a href="<?php echo site_url('admin/sync_locations'); ?>" class="btn btn-default">
                                <i class="fa fa-times"></i> <?php echo get_phrase('cancel'); ?>
                            </a>
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>
        
    </div>
</div>
