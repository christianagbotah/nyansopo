<?php echo form_open(site_url('sms_automation/test_automation/' . $automation_id), array('id' => 'testForm')); ?>
    <div class="space-y-5">
        <div>
            <label class="block text-lg font-semibold text-gray-800 mb-2">
                <?php echo get_phrase('test_phone_number'); ?> <span class="text-red-500">*</span>
            </label>
            <input type="tel" name="test_phone" class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" required placeholder="+233XXXXXXXXX">
            <p class="text-base text-gray-600 mt-2">Enter phone number to receive test SMS</p>
        </div>
    </div>

    <div class="flex gap-3 mt-6 pt-4 border-t">
        <button type="button" class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-lg font-semibold rounded-lg transition" data-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-lg font-semibold rounded-lg shadow-lg transition transform hover:scale-105">
            📤 Send Test
        </button>
    </div>
<?php echo form_close(); ?>

<script>
$('#testForm').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('Sending test...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('Operation failed', 'error');
    });
});
</script>
