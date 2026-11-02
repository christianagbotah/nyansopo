<style>
.wizard-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
.wizard-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 16px 16px 0 0; text-align: center; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3); }
.wizard-header h1 { margin: 0 0 10px 0; font-size: 32px; font-weight: 700; }
.wizard-header p { margin: 0; opacity: 0.95; font-size: 16px; }
.wizard-body { background: white; border-radius: 0 0 16px 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.wizard-progress { padding: 30px 40px; border-bottom: 1px solid #e5e7eb; }
.progress-steps { display: flex; justify-content: space-between; align-items: center; position: relative; margin-bottom: 20px; }
.progress-steps::before { content: ''; position: absolute; top: 20px; left: 0; right: 0; height: 3px; background: #e5e7eb; z-index: 0; }
.progress-line { position: absolute; top: 20px; left: 0; height: 3px; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); z-index: 1; transition: width 0.3s ease; }
.progress-step { position: relative; z-index: 2; text-align: center; flex: 1; }
.step-circle { width: 40px; height: 40px; border-radius: 50%; background: white; border: 3px solid #e5e7eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: 700; color: #9ca3af; transition: all 0.3s; }
.progress-step.active .step-circle { border-color: #667eea; background: #667eea; color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
.progress-step.completed .step-circle { border-color: #10b981; background: #10b981; color: white; }
.step-label { font-size: 13px; color: #6b7280; font-weight: 600; }
.progress-step.active .step-label { color: #667eea; }
.progress-step.completed .step-label { color: #10b981; }
.wizard-content { padding: 40px; }
.step-title { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; }
.step-title i { color: #667eea; }
.step-description { font-size: 16px; color: #6b7280; margin-bottom: 30px; }
.info-card { background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%); border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
.info-card i { color: #3b82f6; font-size: 20px; margin-right: 10px; }
.success-card { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #10b981; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
.success-card i { color: #10b981; font-size: 20px; margin-right: 10px; }
.warning-card { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
.warning-card i { color: #f59e0b; font-size: 20px; margin-right: 10px; }
.danger-card { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-left: 4px solid #ef4444; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
.danger-card i { color: #ef4444; font-size: 20px; margin-right: 10px; }
.requirements-table { width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
.requirements-table thead { background: #f9fafb; }
.requirements-table th { padding: 15px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
.requirements-table td { padding: 15px; border-bottom: 1px solid #f3f4f6; }
.requirements-table tr:last-child td { border-bottom: none; }
.requirements-table tr.success { background: #f0fdf4; }
.requirements-table tr.danger { background: #fef2f2; }
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.danger { background: #fee2e2; color: #991b1b; }
.status-badge.warning { background: #fef3c7; color: #92400e; }
.form-group-modern { margin-bottom: 25px; }
.form-group-modern label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
.form-group-modern input, .form-group-modern select { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
.form-group-modern input:focus, .form-group-modern select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.form-group-modern small { display: block; margin-top: 6px; color: #6b7280; font-size: 13px; }
.btn-wizard { padding: 12px 28px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn-wizard-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-wizard-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); color: white; text-decoration: none; }
.btn-wizard-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-wizard-success:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); color: white; text-decoration: none; }
.btn-wizard-secondary { background: #f3f4f6; color: #374151; }
.btn-wizard-secondary:hover { background: #e5e7eb; text-decoration: none; }
.wizard-navigation { padding: 30px 40px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
.completion-icon { font-size: 80px; color: #10b981; margin-bottom: 20px; text-align: center; }
.quick-ref-table { width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin: 25px 0; }
.quick-ref-table th { background: #f9fafb; padding: 15px; text-align: left; font-weight: 600; color: #374151; width: 30%; }
.quick-ref-table td { padding: 15px; background: white; }
.code-block { background: #1f2937; color: #e5e7eb; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; overflow-x: auto; margin: 20px 0; }
.form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
.form-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .form-row-3 { grid-template-columns: 1fr; } .wizard-content { padding: 20px; } .wizard-progress { padding: 20px; } .wizard-navigation { padding: 20px; flex-direction: column; gap: 15px; } }
</style>

<div class="wizard-container">
    <div class="wizard-header">
        <h1 style="color: white;"><i class="fa fa-magic" style="color: white;"></i> Cloud Sync Setup Wizard</h1>
        <p>Configure your Local WAMP + Cloud Sync system in 7 easy steps</p>
    </div>
    
    <div class="wizard-body">
        <div class="wizard-progress">
            <div class="progress-steps">
                <div class="progress-line" style="width: <?php echo (($current_step - 1) / ($total_steps - 1)) * 100; ?>%;"></div>
                <?php for ($i = 1; $i <= $total_steps; $i++): ?>
                <div class="progress-step <?php echo $i < $current_step ? 'completed' : ($i == $current_step ? 'active' : ''); ?>">
                    <div class="step-circle">
                        <?php if ($i < $current_step): ?>
                            <i class="fa fa-check"></i>
                        <?php else: ?>
                            <?php echo $i; ?>
                        <?php endif; ?>
                    </div>
                    <div class="step-label">
                        <?php 
                        $labels = ['Welcome', 'Network', 'Credentials', 'Install', 'Test', 'Schedule', 'Complete'];
                        echo $labels[$i - 1];
                        ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="wizard-content">
            <?php if ($current_step == 1): ?>
                <!-- Step 1: Welcome & System Requirements -->
                <div class="step-title">
                    <i class="fa fa-rocket"></i>
                    Welcome to Cloud Sync Setup
                </div>
                <p class="step-description">
                    Let's configure your Local WAMP server to sync with your cloud database. First, we'll check if your system meets all requirements.
                </p>

                <?php if (isset($all_checks_passed) && $all_checks_passed): ?>
                    <div class="success-card">
                        <i class="fa fa-check-circle"></i>
                        <strong>All system requirements met!</strong> Your server is ready for Cloud Sync setup.
                    </div>
                <?php else: ?>
                    <div class="warning-card">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Some requirements are not met.</strong> Please resolve the issues below before continuing.
                    </div>
                <?php endif; ?>

                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th>Requirement</th>
                            <th>Status</th>
                            <th>Current Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($checks)): foreach ($checks as $check): ?>
                        <tr class="<?php echo $check['status'] ? 'success' : 'danger'; ?>">
                            <td><?php echo $check['name']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $check['status'] ? 'success' : 'danger'; ?>">
                                    <i class="fa fa-<?php echo $check['status'] ? 'check' : 'times'; ?>"></i>
                                    <?php echo $check['status'] ? 'Passed' : 'Failed'; ?>
                                </span>
                            </td>
                            <td><?php echo $check['value']; ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

            <?php elseif ($current_step == 2): ?>
                <!-- Step 2: Network Configuration -->
                <div class="step-title">
                    <i class="fa fa-network-wired"></i>
                    Network Configuration
                </div>
                <p class="step-description">
                    Verify your local network settings. Your WAMP server needs a static IP address for reliable sync operations.
                </p>

                <div class="info-card">
                    <i class="fa fa-info-circle"></i>
                    <strong>Current Network Information</strong>
                </div>

                <table class="quick-ref-table">
                    <tr>
                        <th>Server Name</th>
                        <td><?php echo isset($server_name) ? $server_name : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th>Local IP Address</th>
                        <td><?php echo isset($local_ip) ? $local_ip : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th>IP Type</th>
                        <td>
                            <?php if (isset($is_static_ip) && $is_static_ip): ?>
                                <span class="status-badge success"><i class="fa fa-check"></i> Static IP (192.168.x.x)</span>
                            <?php else: ?>
                                <span class="status-badge warning"><i class="fa fa-exclamation-triangle"></i> May not be static</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php if (isset($is_static_ip) && !$is_static_ip): ?>
                    <div class="warning-card">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Your IP address may not be static. For reliable sync operations, configure a static IP in your router settings (typically 192.168.x.x range).
                    </div>
                <?php else: ?>
                    <div class="success-card">
                        <i class="fa fa-check-circle"></i>
                        <strong>Good!</strong> Your IP appears to be in the static range. Make sure it's configured as static in your router.
                    </div>
                <?php endif; ?>

            <?php elseif ($current_step == 3): ?>
                <!-- Step 3: Remote Server Credentials -->
                <div class="step-title">
                    <i class="fa fa-key"></i>
                    Remote Server Credentials
                </div>
                <p class="step-description">
                    Enter your cloud database credentials. We'll test the connection to ensure everything works.
                </p>

                <?php if (isset($test_result)): ?>
                    <?php if ($test_result['success']): ?>
                        <div class="success-card">
                            <i class="fa fa-check-circle"></i>
                            <strong>Connection Successful!</strong> <?php echo $test_result['message']; ?>
                        </div>
                    <?php else: ?>
                        <div class="danger-card">
                            <i class="fa fa-times-circle"></i>
                            <strong>Connection Failed:</strong> <?php echo $test_result['message']; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php echo form_open('setup_wizard/step/3', [
                    'class' => 'wizard-form ajax-form',
                    'id' => 'step-3-form',
                    'data-step' => '3',
                    'data-ajax' => 'true'
                ]); ?>
                    <div class="form-row">
                        <div class="form-group-modern">
                            <label>Remote Host</label>
                            <input type="text" name="remote_host" value="<?php echo isset($remote_host) ? $remote_host : ''; ?>" placeholder="e.g., db.example.com" required>
                            <small>Your cloud database server address</small>
                        </div>
                        <div class="form-group-modern">
                            <label>Port</label>
                            <input type="number" name="remote_port" value="<?php echo isset($remote_port) ? $remote_port : '3306'; ?>" placeholder="3306" required>
                            <small>Usually 3306 for MySQL</small>
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label>Database Name</label>
                        <input type="text" name="remote_database" value="<?php echo isset($remote_database) ? $remote_database : ''; ?>" placeholder="database_name" required>
                        <small>The name of your cloud database</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group-modern">
                            <label>Username</label>
                            <input type="text" name="remote_user" value="<?php echo isset($remote_user) ? $remote_user : ''; ?>" placeholder="db_user" required>
                        </div>
                        <div class="form-group-modern">
                            <label>Password</label>
                            <input type="password" name="remote_pass" value="<?php echo isset($remote_password) ? $remote_password : ''; ?>" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" name="test_connection" value="1" class="btn-wizard btn-wizard-primary">
                        <i class="fa fa-plug"></i> Test Connection
                    </button>
                <?php echo form_close(); ?>

            <?php elseif ($current_step == 4): ?>
                <!-- Step 4: Install Sync Columns -->
                <div class="step-title">
                    <i class="fa fa-database"></i>
                    Install Sync Columns
                </div>
                <p class="step-description">
                    Add sync tracking columns to your database tables. This enables the system to track changes and sync data.
                </p>

                <?php if (isset($columns_installed) && $columns_installed): ?>
                    <div class="success-card">
                        <i class="fa fa-check-circle"></i>
                        <strong>Sync columns are installed!</strong> Your database is ready for synchronization.
                    </div>
                <?php else: ?>
                    <div class="info-card">
                        <i class="fa fa-info-circle"></i>
                        <strong>Ready to install sync columns.</strong> This will add tracking fields to your database tables.
                    </div>

                    <?php if (isset($installation_result)): ?>
                        <?php if ($installation_result['success']): ?>
                            <div class="success-card">
                                <i class="fa fa-check-circle"></i>
                                <strong>Installation Complete!</strong> <?php echo $installation_result['message']; ?>
                            </div>
                        <?php else: ?>
                            <div class="danger-card">
                                <i class="fa fa-times-circle"></i>
                                <strong>Installation Failed:</strong> <?php echo $installation_result['message']; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php echo form_open('setup_wizard/step/4', [
                        'class' => 'wizard-form ajax-form',
                        'id' => 'step-4-form',
                        'data-step' => '4',
                        'data-ajax' => 'true'
                    ]); ?>
                        <button type="submit" name="install_columns" value="1" class="btn-wizard btn-wizard-primary">
                            <i class="fa fa-download"></i> Install Sync Columns
                        </button>
                    <?php echo form_close(); ?>
                <?php endif; ?>

            <?php elseif ($current_step == 5): ?>
                <!-- Step 5: Test Sync -->
                <div class="step-title">
                    <i class="fa fa-sync"></i>
                    Test Synchronization
                </div>
                <p class="step-description">
                    Run a test sync to verify everything is working correctly.
                </p>

                <?php if (isset($sync_result)): ?>
                    <?php if ($sync_result['success']): ?>
                        <div class="success-card">
                            <i class="fa fa-check-circle"></i>
                            <strong>Sync Test Successful!</strong> <?php echo $sync_result['message']; ?>
                        </div>
                    <?php else: ?>
                        <div class="danger-card">
                            <i class="fa fa-times-circle"></i>
                            <strong>Sync Test Failed:</strong> <?php echo $sync_result['message']; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="info-card">
                        <i class="fa fa-info-circle"></i>
                        <strong>Ready to test.</strong> Click below to run a test synchronization.
                    </div>

                    <?php echo form_open('setup_wizard/step/5', [
                        'class' => 'wizard-form ajax-form',
                        'id' => 'step-5-form',
                        'data-step' => '5',
                        'data-ajax' => 'true'
                    ]); ?>
                        <button type="submit" name="run_test_sync" value="1" class="btn-wizard btn-wizard-primary">
                            <i class="fa fa-play"></i> Run Test Sync
                        </button>
                    <?php echo form_close(); ?>
                <?php endif; ?>

            <?php elseif ($current_step == 6): ?>
                <!-- Step 6: Schedule Sync -->
                <div class="step-title">
                    <i class="fa fa-clock"></i>
                    Schedule Automatic Sync
                </div>
                <p class="step-description">
                    Set up automatic synchronization using Windows Task Scheduler.
                </p>

                <?php if (isset($batch_file_created) && $batch_file_created): ?>
                    <div class="success-card">
                        <i class="fa fa-check-circle"></i>
                        <strong>Batch file created!</strong> Follow the instructions below to schedule it.
                    </div>
                <?php endif; ?>

                <div class="info-card">
                    <i class="fa fa-info-circle"></i>
                    <strong>Setup Instructions:</strong>
                    <ol style="margin: 15px 0 0 20px; line-height: 1.8;">
                        <li>A batch file has been created at: <code><?php echo isset($batch_file_path) ? $batch_file_path : 'sync_to_cloud.bat'; ?></code></li>
                        <li>Open Windows Task Scheduler</li>
                        <li>Create a new task to run this batch file</li>
                        <li>Set it to run every 5-10 minutes</li>
                        <li>Configure it to run whether user is logged in or not</li>
                    </ol>
                </div>

                <?php echo form_open('setup_wizard/step/6', [
                    'class' => 'wizard-form ajax-form',
                    'id' => 'step-6-form',
                    'data-step' => '6',
                    'data-ajax' => 'true'
                ]); ?>
                    <button type="submit" name="create_batch" value="1" class="btn-wizard btn-wizard-primary">
                        <i class="fa fa-file"></i> Create Batch File
                    </button>
                <?php echo form_close(); ?>

            <?php elseif ($current_step == 7): ?>
                <!-- Step 7: Complete -->
                <div class="completion-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="step-title" style="justify-content: center;">
                    Setup Complete!
                </div>
                <p class="step-description" style="text-align: center;">
                    Your Local WAMP + Cloud Sync system is now configured and ready to use.
                </p>

                <div class="success-card">
                    <i class="fa fa-check-circle"></i>
                    <strong>Congratulations!</strong> You can now manage your sync operations from the dashboard.
                </div>

                <div class="info-card">
                    <i class="fa fa-lightbulb"></i>
                    <strong>Next Steps:</strong>
                    <ul style="margin: 15px 0 0 20px; line-height: 1.8;">
                        <li>Visit the Sync Dashboard to monitor sync status</li>
                        <li>Configure sync settings as needed</li>
                        <li>Set up Windows Task Scheduler for automatic sync</li>
                        <li>Test the sync process with real data</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="wizard-navigation">
            <div>
                <?php if ($current_step > 1): ?>
                <a href="<?php echo site_url('setup_wizard/previous_step'); ?>" class="btn-wizard btn-wizard-secondary">
                    <i class="fa fa-arrow-left"></i> Previous
                </a>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($current_step < $total_steps): ?>
                <a href="<?php echo site_url('setup_wizard/next_step'); ?>" class="btn-wizard btn-wizard-primary">
                    Next <i class="fa fa-arrow-right"></i>
                </a>
                <?php else: ?>
                <a href="<?php echo site_url('sync_server/dashboard'); ?>" class="btn-wizard btn-wizard-success">
                    <i class="fa fa-check"></i> Go to Dashboard
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Setup Wizard AJAX Handler -->
<script src="<?php echo base_url('assets/js/setup_wizard_ajax.js'); ?>"></script>