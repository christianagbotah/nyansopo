<?php
/**
 * Add Sync Location View
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
                    <i class="fa fa-plus"></i> <?php echo get_phrase('add_location'); ?>
                </h3>
            </div>
            
            <div class="panel-body">
                <form action="<?php echo site_url('admin/sync_locations/create'); ?>" method="post" class="form-horizontal">
                    
                    <!-- Location Name -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('location_name'); ?> *</label>
                        <div class="col-sm-9">
                            <input type="text" name="location_name" class="form-control" required 
                                   placeholder="e.g., Main Campus, Branch Office, etc.">
                        </div>
                    </div>
                    
                    <!-- Device ID -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('device_id'); ?> *</label>
                        <div class="col-sm-9">
                            <input type="text" name="device_id" class="form-control" required 
                                   value="loc-branch-<?php echo str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT); ?>"
                                   placeholder="Unique device identifier">
                            <small class="text-muted">Must be unique across all locations</small>
                        </div>
                    </div>
                    
                    <!-- API Endpoint -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('api_endpoint'); ?></label>
                        <div class="col-sm-9">
                            <input type="url" name="api_endpoint" class="form-control" 
                                   placeholder="http://192.168.1.100/schoolmanager">
                            <small class="text-muted">Full URL to the location's API endpoint</small>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('status'); ?></label>
                        <div class="col-sm-9">
                            <select name="status" class="form-control">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Priority -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('priority'); ?></label>
                        <div class="col-sm-9">
                            <input type="number" name="priority" class="form-control" value="0" min="0" max="100">
                            <small class="text-muted">Higher priority locations sync first (0-100)</small>
                        </div>
                    </div>
                    
                    <!-- Sync Enabled -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('sync_enabled'); ?></label>
                        <div class="col-sm-9">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="sync_enabled" value="1" checked> Enable synchronization for this location
                            </label>
                        </div>
                    </div>
                    
                    <!-- Contact Email -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('contact_email'); ?></label>
                        <div class="col-sm-9">
                            <input type="email" name="contact_email" class="form-control" 
                                   placeholder="admin@example.com">
                        </div>
                    </div>
                    
                    <!-- Contact Phone -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('contact_phone'); ?></label>
                        <div class="col-sm-9">
                            <input type="tel" name="contact_phone" class="form-control" 
                                   placeholder="+1234567890">
                        </div>
                    </div>
                    
                    <!-- Timezone -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('timezone'); ?></label>
                        <div class="col-sm-9">
                            <select name="timezone" class="form-control">
                                <option value="UTC">UTC</option>
                                <option value="Africa/Accra">Africa/Accra (GMT)</option>
                                <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                                <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                                <option value="America/New_York">America/New_York (EST)</option>
                                <option value="America/Chicago">America/Chicago (CST)</option>
                                <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
                                <option value="Europe/London">Europe/London (GMT)</option>
                                <option value="Asia/Dubai">Asia/Dubai (GST)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('description'); ?></label>
                        <div class="col-sm-9">
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="Additional notes about this location"></textarea>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo get_phrase('save_location'); ?>
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
