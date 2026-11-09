<?php
$automation_id = isset($param1) ? $param1 : '';
$automation = array();

if($automation_id) {
    $result = $this->db->where('id', $automation_id)->get('sms_automations')->row_array();
    if($result) {
        $automation = $result;
    }
}

$recipients_value = '';
if(isset($automation['recipients'])) {
    $recipients_value = $automation['recipients'];
}
?>

<?php echo form_open(site_url('sms_automation/save'), array('id' => 'smsAutomationForm')); ?>
    <?php if($automation_id): ?>
        <input type="hidden" name="automation_id" value="<?php echo $automation_id; ?>">
    <?php endif; ?>

    <div class="space-y-6">
        <div>
            <label class="block text-lg font-semibold text-gray-800 mb-2">
                <?php echo get_phrase('automation_name'); ?> <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" value="<?php echo isset($automation['name']) ? $automation['name'] : ''; ?>" class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" required placeholder="e.g., Payment Confirmation SMS">
            <p class="text-base text-gray-600 mt-2">Give this automation a descriptive name</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-lg font-semibold text-gray-800 mb-2">
                    <?php echo get_phrase('trigger_event'); ?> <span class="text-red-500">*</span>
                </label>
                <select name="trigger_event" class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" required>
                    <option value="">Select Trigger Event</option>
                    <option value="payment_received" <?php echo (isset($automation['trigger_event']) && $automation['trigger_event'] == 'payment_received') ? 'selected' : ''; ?>>💰 Payment Received</option>
                    <option value="invoice_created" <?php echo (isset($automation['trigger_event']) && $automation['trigger_event'] == 'invoice_created') ? 'selected' : ''; ?>>📄 Invoice Created</option>
                    <option value="invoice_due_soon" <?php echo (isset($automation['trigger_event']) && $automation['trigger_event'] == 'invoice_due_soon') ? 'selected' : ''; ?>>⏰ Invoice Due Soon</option>
                    <option value="invoice_overdue" <?php echo (isset($automation['trigger_event']) && $automation['trigger_event'] == 'invoice_overdue') ? 'selected' : ''; ?>>⚠️ Invoice Overdue</option>
                    <option value="student_admission" <?php echo (isset($automation['trigger_event']) && $automation['trigger_event'] == 'student_admission') ? 'selected' : ''; ?>>🎓 Student Admission</option>
                    <option value="exam_result" <?php echo (isset($automation['trigger_event']) && $automation['trigger_event'] == 'exam_result') ? 'selected' : ''; ?>>📊 Exam Result Published</option>
                    <option value="attendance_alert" <?php echo (isset($automation['trigger_event']) && $automation['trigger_event'] == 'attendance_alert') ? 'selected' : ''; ?>>📅 Attendance Alert</option>
                </select>
                <p class="text-base text-gray-600 mt-2">When should this SMS be triggered?</p>
            </div>

            <div>
                <label class="block text-lg font-semibold text-gray-800 mb-2">
                    <?php echo get_phrase('recipients'); ?> <span class="text-red-500">*</span>
                </label>
                <select name="recipients" class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" required>
                    <option value="">Select Recipients</option>
                    <option value="parents" <?php echo ($recipients_value == 'parents') ? 'selected' : ''; ?>>👨👩👧 Parents</option>
                    <option value="students" <?php echo ($recipients_value == 'students') ? 'selected' : ''; ?>>🎓 Students</option>
                    <option value="teachers" <?php echo ($recipients_value == 'teachers') ? 'selected' : ''; ?>>👨🏫 Teachers</option>
                    <option value="all" <?php echo ($recipients_value == 'all') ? 'selected' : ''; ?>>👔 All Users</option>
                </select>
                <p class="text-base text-gray-600 mt-2">Who will receive this SMS?</p>
            </div>
        </div>

        <div>
            <label class="block text-lg font-semibold text-gray-800 mb-2">
                <?php echo get_phrase('message_template'); ?> <span class="text-red-500">*</span>
            </label>
            <textarea name="message_template" rows="5" class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none" required placeholder="Enter your message template..."><?php echo isset($automation['message_template']) ? $automation['message_template'] : ''; ?></textarea>
            <p class="text-base text-gray-600 mt-2">
                💡 Available: <code class="bg-gray-100 px-2 py-1 rounded text-base">{student_name}</code> <code class="bg-gray-100 px-2 py-1 rounded text-base">{amount}</code> <code class="bg-gray-100 px-2 py-1 rounded text-base">{invoice_code}</code> <code class="bg-gray-100 px-2 py-1 rounded text-base">{due_date}</code> <code class="bg-gray-100 px-2 py-1 rounded text-base">{school_name}</code>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-lg font-semibold text-gray-800 mb-2">
                    <?php echo get_phrase('trigger_days'); ?>
                </label>
                <input type="number" name="trigger_days" value="<?php echo isset($automation['trigger_days']) ? $automation['trigger_days'] : '0'; ?>" class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" placeholder="0">
                <p class="text-base text-gray-600 mt-2">Days before (-) or after (+) the event</p>
            </div>

            <div>
                <label class="block text-lg font-semibold text-gray-800 mb-2">
                    <?php echo get_phrase('status'); ?>
                </label>
                <div class="flex items-center h-12 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" <?php echo (!isset($automation['is_active']) || $automation['is_active'] == 1) ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-indigo-600"></div>
                        <span class="ml-3 text-lg font-semibold text-gray-800"><?php echo get_phrase('activate_immediately'); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-3 mt-6 pt-4 border-t">
        <button type="button" class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-lg font-semibold rounded-lg transition" data-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-lg font-semibold rounded-lg shadow-lg transition transform hover:scale-105">
            💾 Save Automation
        </button>
    </div>
<?php echo form_close(); ?>

<script>
$('#smsAutomationForm').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('Saving...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(function() { location.reload(); }, 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('Operation failed', 'error');
    });
});
</script>
