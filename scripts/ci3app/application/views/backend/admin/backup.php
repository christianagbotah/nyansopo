<div class="row">
    <div class="col-md-12">
        
        <!-- Page Header -->
        <div class="panel panel-primary" style="border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px 8px 0 0;">
                <h3 class="panel-title" style="font-size: 20px; font-weight: 600;">
                    <i class="fa fa-database"></i> Database Backup & Restore
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Flash Messages -->
                <?php if($this->session->flashdata('flash_message')): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fa fa-check-circle"></i> <?php echo $this->session->flashdata('flash_message'); ?>
                </div>
                <?php endif; ?>
                
                <?php if($this->session->flashdata('error_message')): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fa fa-exclamation-triangle"></i> <?php echo $this->session->flashdata('error_message'); ?>
                </div>
                <?php endif; ?>
                
                <!-- Info Alert -->
                <div class="alert alert-info" style="background-color: #e3f2fd; border-color: #2196F3; color: #0d47a1;">
                    <i class="fa fa-info-circle"></i> <strong>Important:</strong> 
                    Backups are stored in the <code>backup/database_backup/</code> directory. 
                    Make sure to download and store backups in a safe location regularly.
                    <br><strong>Warning:</strong> Restoring a backup will replace all current database data!
                </div>
                
                <!-- Create Backup Button -->
                <div class="row" style="margin-bottom: 30px;">
                    <div class="col-md-12">
                        <form method="post" action="<?php echo site_url('admin/backup'); ?>" onsubmit="return confirm('Create a new database backup?');">
                            <input type="hidden" name="action" value="create_backup">
                            <button type="submit" class="btn btn-lg" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 30px; border-radius: 8px; border: none; box-shadow: 0 4px 12px rgba(56, 239, 125, 0.3);">
                                <i class="fa fa-plus-circle"></i> Create New Backup
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Backup Files List -->
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #f5f5f5; border-bottom: 2px solid #e0e0e0;">
                        <h4 class="panel-title">
                            <i class="fa fa-list"></i> Available Backups (<?php echo count($backup_files); ?>)
                        </h4>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        
                        <?php if(empty($backup_files)): ?>
                        <div class="text-center" style="padding: 50px;">
                            <i class="fa fa-database" style="font-size: 80px; color: #ccc;"></i>
                            <h4 style="color: #999; margin-top: 20px;">No backups found</h4>
                            <p style="color: #999;">Create your first backup to get started</p>
                        </div>
                        <?php else: ?>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" style="margin-bottom: 0;">
                                <thead style="background-color: #f9f9f9;">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th><i class="fa fa-file"></i> Filename</th>
                                        <th style="width: 150px;"><i class="fa fa-hdd"></i> Size</th>
                                        <th style="width: 200px;"><i class="fa fa-clock"></i> Created Date</th>
                                        <th style="width: 250px; text-align: center;"><i class="fa fa-cogs"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    foreach($backup_files as $file): 
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <i class="fa fa-file-code" style="color: #2196F3;"></i> 
                                            <strong><?php echo $file['filename']; ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $size_mb = round($file['size'] / 1024 / 1024, 2);
                                            $size_kb = round($file['size'] / 1024, 2);
                                            echo $size_mb > 0 ? $size_mb . ' MB' : $size_kb . ' KB';
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($file['date'])); ?></td>
                                        <td style="text-align: center;">
                                            <!-- Download Button -->
                                            <form method="post" action="<?php echo site_url('admin/backup'); ?>" style="display: inline-block;">
                                                <input type="hidden" name="action" value="download">
                                                <input type="hidden" name="filename" value="<?php echo $file['filename']; ?>">
                                                <button type="submit" class="btn btn-sm btn-info" title="Download Backup">
                                                    <i class="fa fa-download"></i> Download
                                                </button>
                                            </form>
                                            
                                            <!-- Restore Button -->
                                            <form method="post" action="<?php echo site_url('admin/backup'); ?>" style="display: inline-block;" onsubmit="return confirm('WARNING: This will replace ALL current database data with this backup. Are you absolutely sure?');">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="filename" value="<?php echo $file['filename']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Restore Backup">
                                                    <i class="fa fa-undo"></i> Restore
                                                </button>
                                            </form>
                                            
                                            <!-- Delete Button -->
                                            <form method="post" action="<?php echo site_url('admin/backup'); ?>" style="display: inline-block;" onsubmit="return confirm('Delete this backup file permanently?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="filename" value="<?php echo $file['filename']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Backup">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php endif; ?>
                        
                    </div>
                </div>
                
                <!-- Help Section -->
                <div class="panel panel-default" style="margin-top: 20px;">
                    <div class="panel-heading" style="background-color: #fff3e0; border-bottom: 2px solid #ff9800;">
                        <h4 class="panel-title">
                            <i class="fa fa-question-circle"></i> How to Use
                        </h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5><i class="fa fa-plus-circle" style="color: #4CAF50;"></i> Creating Backups</h5>
                                <p>Click "Create New Backup" to generate a complete database backup. The file will be saved with a timestamp.</p>
                            </div>
                            <div class="col-md-4">
                                <h5><i class="fa fa-download" style="color: #2196F3;"></i> Downloading Backups</h5>
                                <p>Download backup files to your computer for safekeeping. Store them in a secure location outside the server.</p>
                            </div>
                            <div class="col-md-4">
                                <h5><i class="fa fa-undo" style="color: #FF9800;"></i> Restoring Backups</h5>
                                <p>Restore a backup to revert your database to a previous state. <strong>Warning: This will overwrite all current data!</strong></p>
                            </div>
                        </div>
                        <hr>
                        <div class="alert alert-warning" style="margin-bottom: 0;">
                            <i class="fa fa-exclamation-triangle"></i> <strong>Best Practices:</strong>
                            <ul style="margin-top: 10px; margin-bottom: 0;">
                                <li>Create backups regularly (daily or weekly depending on data changes)</li>
                                <li>Download and store backups externally (not just on the server)</li>
                                <li>Test your backups periodically to ensure they work</li>
                                <li>Always create a fresh backup before major updates or changes</li>
                                <li>Delete old backups to save disk space (keep at least 3-5 recent backups)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<style>
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f5f5f5;
}

.alert {
    border-radius: 6px;
}

code {
    background-color: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    color: #e91e63;
}
</style>
