<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-clock-o"></i> <?php echo get_phrase('automated_bill_reminder_setup'); ?></h3>
            </div>
            <div class="panel-body" style="padding: 30px;">
                
                <!-- Step 1 -->
                <div class="setup-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4><?php echo get_phrase('create_sms_automation'); ?></h4>
                        <p><?php echo get_phrase('first_create_monthly_bill_reminder_automation'); ?></p>
                        <a href="<?php echo base_url(); ?>admin/sms_automation" class="btn btn-primary">
                            <i class="fa fa-mobile"></i> <?php echo get_phrase('go_to_sms_automation'); ?>
                        </a>
                        <div class="help-text">
                            <strong><?php echo get_phrase('settings'); ?>:</strong><br>
                            - <?php echo get_phrase('name'); ?>: Monthly Bill Reminder<br>
                            - <?php echo get_phrase('trigger_event'); ?>: Monthly Bill Reminder (Scheduled)<br>
                            - <?php echo get_phrase('recipients'); ?>: parents<br>
                            - <?php echo get_phrase('status'); ?>: Active
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="setup-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4><?php echo get_phrase('setup_cpanel_cron_job'); ?></h4>
                        <p><?php echo get_phrase('login_to_cpanel_and_navigate_to_cron_jobs'); ?></p>
                        
                        <div class="cron-options">
                            <div class="cron-option">
                                <h5><i class="fa fa-calendar"></i> <?php echo get_phrase('monthly_1st_day_8am'); ?></h5>
                                <div class="cron-command">
                                    <code id="cron1">0 8 1 * * /usr/bin/php <?php echo FCPATH; ?>index.php cron monthly_bill_reminders</code>
                                    <button class="btn btn-sm btn-success copy-btn" onclick="copyToClipboard('cron1')">
                                        <i class="fa fa-copy"></i> <?php echo get_phrase('copy'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="cron-option">
                                <h5><i class="fa fa-calendar-check-o"></i> <?php echo get_phrase('twice_monthly_1st_15th_8am'); ?></h5>
                                <div class="cron-command">
                                    <code id="cron2">0 8 1,15 * * /usr/bin/php <?php echo FCPATH; ?>index.php cron monthly_bill_reminders</code>
                                    <button class="btn btn-sm btn-success copy-btn" onclick="copyToClipboard('cron2')">
                                        <i class="fa fa-copy"></i> <?php echo get_phrase('copy'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="cron-option">
                                <h5><i class="fa fa-calendar-o"></i> <?php echo get_phrase('custom_15th_day_9am'); ?></h5>
                                <div class="cron-command">
                                    <code id="cron3">0 9 15 * * /usr/bin/php <?php echo FCPATH; ?>index.php cron monthly_bill_reminders</code>
                                    <button class="btn btn-sm btn-success copy-btn" onclick="copyToClipboard('cron3')">
                                        <i class="fa fa-copy"></i> <?php echo get_phrase('copy'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info" style="margin-top: 20px;">
                            <i class="fa fa-info-circle"></i> 
                            <strong><?php echo get_phrase('note'); ?>:</strong> 
                            <?php echo get_phrase('php_path_may_vary_check_with_hosting_provider'); ?>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="setup-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4><?php echo get_phrase('test_the_automation'); ?></h4>
                        <p><?php echo get_phrase('test_manually_before_scheduling'); ?></p>
                        <button class="btn btn-warning" onclick="testCronJob()">
                            <i class="fa fa-flask"></i> <?php echo get_phrase('test_now'); ?>
                        </button>
                        <div id="testResult" style="margin-top: 15px;"></div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="help-section">
                    <h4><i class="fa fa-question-circle"></i> <?php echo get_phrase('need_help'); ?>?</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="help-card">
                                <h5><?php echo get_phrase('cron_schedule_format'); ?></h5>
                                <pre>* * * * * command
│ │ │ │ │
│ │ │ │ └─ Day of week (0-7)
│ │ │ └─── Month (1-12)
│ │ └───── Day of month (1-31)
│ └─────── Hour (0-23)
└───────── Minute (0-59)</pre>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="help-card">
                                <h5><?php echo get_phrase('common_issues'); ?></h5>
                                <ul>
                                    <li><?php echo get_phrase('check_php_path_with_hosting'); ?></li>
                                    <li><?php echo get_phrase('ensure_sms_automation_is_active'); ?></li>
                                    <li><?php echo get_phrase('verify_hubtel_credentials'); ?></li>
                                    <li><?php echo get_phrase('check_parent_phone_numbers'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documentation Link -->
                <div class="text-center" style="margin-top: 30px;">
                    <a href="<?php echo base_url(); ?>CRON_AUTO_SMS_SETUP_GUIDE.md" target="_blank" class="btn btn-info btn-lg">
                        <i class="fa fa-book"></i> <?php echo get_phrase('view_full_documentation'); ?>
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.setup-step {
    display: flex;
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 2px solid #e0e0e0;
}

.step-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    margin-right: 20px;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-content h4 {
    margin-top: 0;
    color: #2c3e50;
    font-size: 20px;
    margin-bottom: 10px;
}

.help-text {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    border-left: 4px solid #667eea;
}

.cron-options {
    margin-top: 20px;
}

.cron-option {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.cron-option h5 {
    margin-top: 0;
    color: #2c3e50;
    margin-bottom: 10px;
}

.cron-command {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #2c3e50;
    padding: 12px;
    border-radius: 6px;
}

.cron-command code {
    flex: 1;
    color: #10b981;
    font-size: 13px;
    background: transparent;
    padding: 0;
}

.copy-btn {
    flex-shrink: 0;
}

.help-section {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 12px;
    margin-top: 40px;
}

.help-section h4 {
    margin-top: 0;
    color: #2c3e50;
    margin-bottom: 20px;
}

.help-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: 100%;
}

.help-card h5 {
    margin-top: 0;
    color: #667eea;
    margin-bottom: 15px;
}

.help-card pre {
    background: #2c3e50;
    color: #10b981;
    padding: 15px;
    border-radius: 6px;
    font-size: 12px;
}

.help-card ul {
    margin: 0;
    padding-left: 20px;
}

.help-card li {
    margin-bottom: 8px;
}
</style>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(function() {
        showAjaxModal_alert('<?php echo get_phrase('copied_to_clipboard'); ?>!', 'success', false);
    }, function() {
        showAjaxModal_alert('<?php echo get_phrase('failed_to_copy'); ?>', 'error');
    });
}

function testCronJob() {
    showAjaxModal_alert('<?php echo get_phrase('testing'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo base_url(); ?>cron/monthly_bill_reminders',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#testResult').html(`
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> 
                        <strong>${response.message}</strong><br>
                        ${response.count} parent(s) received bill reminders.
                    </div>
                `);
                showAjaxModal_alert(response.message, 'success', false);
            } else {
                $('#testResult').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-circle"></i> 
                        <strong><?php echo get_phrase('test_failed'); ?>:</strong> ${response.message}
                    </div>
                `);
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            $('#testResult').html(`
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i> 
                    <strong><?php echo get_phrase('error'); ?>:</strong> <?php echo get_phrase('failed_to_connect_to_cron_endpoint'); ?>
                </div>
            `);
            showAjaxModal_alert('<?php echo get_phrase('failed_to_test_cron_job'); ?>', 'error');
        }
    });
}
</script>
