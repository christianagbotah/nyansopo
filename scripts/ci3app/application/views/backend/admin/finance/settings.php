<?php
include APPPATH . 'views/backend/components/enterprise_ui_components.php';
$currency = get_settings('currency');
?>

<!-- Page Header -->
<?php render_page_header(
    get_phrase('finance_settings'),
    get_phrase('configure_late_payment_penalties_and_restrictions')
); ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Settings Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-white"><?php echo get_phrase('late_payment_settings'); ?></h2>
                        <p class="text-base text-white opacity-90"><?php echo get_phrase('configure_penalties_and_grace_periods'); ?></p>
                    </div>
                </div>
            </div>
            
            <form id="settingsForm" action="<?php echo site_url('finance/settings/update'); ?>" method="post" class="p-6 space-y-6">
                <!-- Grace Period & Fee Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-base font-medium text-gray-700 mb-2">
                            <?php echo get_phrase('grace_period_days'); ?>
                        </label>
                        <div class="relative">
                            <input type="number" name="grace_period_days" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   value="<?php echo $settings['grace_period_days']; ?>" required>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500 text-base"><?php echo get_phrase('days'); ?></span>
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-gray-500"><?php echo get_phrase('days_before_late_fee_applies'); ?></p>
                    </div>

                    <div>
                        <label class="block text-base font-medium text-gray-700 mb-2">
                            <?php echo get_phrase('late_fee_type'); ?>
                        </label>
                        <select name="late_fee_type" id="late-fee-type" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <option value="fixed" <?php echo $settings['late_fee_type'] == 'fixed' ? 'selected' : ''; ?>>
                                <?php echo get_phrase('fixed_amount'); ?>
                            </option>
                            <option value="percentage" <?php echo $settings['late_fee_type'] == 'percentage' ? 'selected' : ''; ?>>
                                <?php echo get_phrase('percentage'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Late Fee Value -->
                <div>
                    <label class="block text-base font-medium text-gray-700 mb-2">
                        <?php echo get_phrase('late_fee_value'); ?>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" id="fee-prefix">
                            <span class="text-gray-500 text-base"><?php echo $currency; ?></span>
                        </div>
                        <input type="number" step="0.01" name="late_fee_value" 
                               class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               value="<?php echo $settings['late_fee_value']; ?>" required>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" id="fee-suffix" style="display: none;">
                            <span class="text-gray-500 text-base">%</span>
                        </div>
                    </div>
                </div>

                <!-- Reminder Days -->
                <div>
                    <label class="block text-base font-medium text-gray-700 mb-2">
                        <?php echo get_phrase('reminder_days'); ?>
                    </label>
                    <input type="text" name="reminder_days" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           value="<?php echo $settings['reminder_days']; ?>" 
                           placeholder="7,14,21" required>
                    <p class="mt-1 text-sm text-gray-500"><?php echo get_phrase('comma_separated_days_for_reminders'); ?></p>
                </div>

                <!-- Restrictions Section -->
                <div class="pt-6 border-t border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4"><?php echo get_phrase('access_restrictions'); ?></h3>
                    
                    <div class="space-y-4">
                        <!-- Restrict Exams -->
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="restrict-exams" name="restrict_exams" value="1" 
                                       <?php echo $settings['restrict_exams'] == 1 ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="ml-3">
                                <label for="restrict-exams" class="text-base font-medium text-gray-700">
                                    <?php echo get_phrase('restrict_exam_access'); ?>
                                </label>
                                <p class="text-sm text-gray-500"><?php echo get_phrase('students_with_outstanding_fees_cannot_access_exams'); ?></p>
                            </div>
                        </div>

                        <!-- Restrict Reports -->
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="restrict-reports" name="restrict_reports" value="1" 
                                       <?php echo $settings['restrict_reports'] == 1 ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="ml-3">
                                <label for="restrict-reports" class="text-base font-medium text-gray-700">
                                    <?php echo get_phrase('restrict_report_access'); ?>
                                </label>
                                <p class="text-sm text-gray-500"><?php echo get_phrase('students_with_outstanding_fees_cannot_view_reports'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-6">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?php echo get_phrase('save_settings'); ?>
                    </button>
                    <button type="reset" 
                            class="inline-flex items-center px-6 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <?php echo get_phrase('reset'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Information Card -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg text-white p-6">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-white"><?php echo get_phrase('information'); ?></h3>
            </div>
            
            <div class="space-y-4 text-base">
                <div>
                    <p class="text-lg font-semibold mb-1"><?php echo get_phrase('grace_period'); ?></p>
                    <p class="text-blue-100"><?php echo get_phrase('number_of_days_after_due_date_before_late_fees_apply'); ?></p>
                </div>
                
                <div>
                    <p class="text-lg font-semibold mb-1"><?php echo get_phrase('late_fees'); ?></p>
                    <p class="text-blue-100"><?php echo get_phrase('penalty_charged_for_late_payments_fixed_or_percentage'); ?></p>
                </div>
                
                <div>
                    <p class="text-lg font-semibold mb-1"><?php echo get_phrase('reminders'); ?></p>
                    <p class="text-blue-100"><?php echo get_phrase('automated_reminders_sent_before_due_date'); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Stats Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-gray-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900"><?php echo get_phrase('quick_stats'); ?></h3>
            </div>
            
            <?php
            $year = get_settings('running_year');
            $term = get_settings('running_term');
            $overdue = $this->db->where('year', $year)->where('term', $term)
                ->where('due >', 0)->count_all_results('invoice');
            ?>
            
            <div class="text-center py-4">
                <div class="text-6xl font-bold text-red-600 mb-2"><?php echo $overdue; ?></div>
                <p class="text-base text-gray-600"><?php echo get_phrase('overdue_invoices'); ?></p>
            </div>
            
            <a href="<?php echo site_url('finance/reports'); ?>" 
               class="block w-full mt-4 px-4 py-2 text-center text-base font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                <?php echo get_phrase('view_detailed_report'); ?>
            </a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    updateFeeTypeDisplay();
    
    $('#late-fee-type').on('change', updateFeeTypeDisplay);
    
    $('#settingsForm').submit(function(e) {
        e.preventDefault();
        showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
        });
    });
});

function updateFeeTypeDisplay() {
    const type = $('#late-fee-type').val();
    if (type === 'percentage') {
        $('#fee-prefix').hide();
        $('#fee-suffix').show();
    } else {
        $('#fee-prefix').show();
        $('#fee-suffix').hide();
    }
}
</script>
