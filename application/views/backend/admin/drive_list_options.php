<style>
#drive-list-content table {
    font-size: 15px;
    line-height: 1.6;
}
#drive-list-content th {
    font-size: 16px;
    font-weight: 700;
    padding: 16px 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
#drive-list-content td {
    padding: 14px;
    font-size: 15px;
    font-weight: 500;
    color: #374151;
}
#drive-list-content tbody tr:hover {
    background-color: #f3f4f6;
    transition: background-color 0.2s;
}
#drive-list-content .total-row td {
    font-weight: 700;
    font-size: 16px;
    background-color: #e5e7eb;
    color: #1f2937;
}
.fee-card.active {
    transform: scale(1.05);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}
.fee-card.active > div {
    border-width: 3px;
    border-color: currentColor;
}
@media (min-width: 768px) {
    #fee-cards-container {
        flex-wrap: nowrap;
        overflow-x: auto;
    }
}
</style>

<div class="w-full mx-auto p-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-5">
            <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                <i class="fa fa-exclamation-triangle"></i>
                <?php echo get_phrase('drive_list_-_students_owing');?>
            </h3>
        </div>
        
        <div class="p-6">
            <div class="flex flex-wrap gap-4 justify-center" id="fee-cards-container">
                <!-- Billed Invoices Card -->
                <div onclick="DriveListManager.load('invoices')" data-type="invoices" class="cursor-pointer group flex-shrink-0 fee-card" style="width: 150px;">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl p-5 border-2 border-blue-200 hover:border-blue-400 transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-1 h-full">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="w-14 h-14 bg-blue-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fa fa-file-invoice text-white text-xl"></i>
                            </div>
                            <h4 class="text-sm font-bold text-blue-900" style="font-size: 15px; line-height: 1.3;"><?php echo get_phrase('billed_invoices');?></h4>
                            <p class="text-xs text-blue-700" style="font-size: 13px;"><?php echo get_phrase('owings');?></p>
                        </div>
                    </div>
                </div>

                <!-- Feeding Fee Card -->
                <div onclick="DriveListManager.load('feeding')" data-type="feeding" class="cursor-pointer group flex-shrink-0 fee-card" style="width: 150px;">
                    <div class="bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl p-5 border-2 border-green-200 hover:border-green-400 transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-1 h-full">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="w-14 h-14 bg-green-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fa fa-utensils text-white text-xl"></i>
                            </div>
                            <h4 class="text-sm font-bold text-green-900" style="font-size: 15px; line-height: 1.3;"><?php echo get_phrase('feeding_fee');?></h4>
                            <p class="text-xs text-green-700" style="font-size: 13px;"><?php echo get_phrase('owings');?></p>
                        </div>
                    </div>
                </div>

                <!-- Classes Fee Card -->
                <div onclick="DriveListManager.load('classes')" data-type="classes" class="cursor-pointer group flex-shrink-0 fee-card" style="width: 150px;">
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-xl p-5 border-2 border-purple-200 hover:border-purple-400 transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-1 h-full">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="w-14 h-14 bg-purple-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fa fa-school text-white text-xl"></i>
                            </div>
                            <h4 class="text-sm font-bold text-purple-900" style="font-size: 15px; line-height: 1.3;"><?php echo get_phrase('classes_fee');?></h4>
                            <p class="text-xs text-purple-700" style="font-size: 13px;"><?php echo get_phrase('owings');?></p>
                        </div>
                    </div>
                </div>

                <!-- Transport Fare Card -->
                <div onclick="DriveListManager.load('transport')" data-type="transport" class="cursor-pointer group flex-shrink-0 fee-card" style="width: 150px;">
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 rounded-xl p-5 border-2 border-orange-200 hover:border-orange-400 transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-1 h-full">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="w-14 h-14 bg-orange-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fa fa-bus text-white text-xl"></i>
                            </div>
                            <h4 class="text-sm font-bold text-orange-900" style="font-size: 15px; line-height: 1.3;"><?php echo get_phrase('transport_fare');?></h4>
                            <p class="text-xs text-orange-700" style="font-size: 13px;"><?php echo get_phrase('owings');?></p>
                        </div>
                    </div>
                </div>

                <!-- Water Fee Card -->
                <div onclick="DriveListManager.load('water')" data-type="water" class="cursor-pointer group flex-shrink-0 fee-card" style="width: 150px;">
                    <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 hover:from-cyan-100 hover:to-cyan-200 rounded-xl p-5 border-2 border-cyan-200 hover:border-cyan-400 transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-1 h-full">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="w-14 h-14 bg-cyan-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fa fa-tint text-white text-xl"></i>
                            </div>
                            <h4 class="text-sm font-bold text-cyan-900" style="font-size: 15px; line-height: 1.3;"><?php echo get_phrase('water_fee');?></h4>
                            <p class="text-xs text-cyan-700" style="font-size: 13px;"><?php echo get_phrase('owings');?></p>
                        </div>
                    </div>
                </div>

                <!-- Breakfast Fee Card -->
                <div onclick="DriveListManager.load('breakfast')" data-type="breakfast" class="cursor-pointer group flex-shrink-0 fee-card" style="width: 150px;">
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 rounded-xl p-5 border-2 border-yellow-200 hover:border-yellow-400 transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-1 h-full">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="w-14 h-14 bg-yellow-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fa fa-coffee text-white text-xl"></i>
                            </div>
                            <h4 class="text-sm font-bold text-yellow-900" style="font-size: 15px; line-height: 1.3;"><?php echo get_phrase('breakfast_fee');?></h4>
                            <p class="text-xs text-yellow-700" style="font-size: 13px;"><?php echo get_phrase('owings');?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="export-buttons" class="mt-6">
                <div class="flex justify-between items-center gap-3 mb-4">
                    <div>
                        <label class="font-bold text-gray-800 mr-3" style="font-size: 16px;"><?php echo get_phrase('filter_by_class');?>:</label>
                        <select id="class-filter" class="form-control" style="display: inline-block; width: auto; min-width: 250px; font-size: 15px; font-weight: 600; padding: 12px 10px; height: 48px; border: 2px solid #e5e7eb; border-radius: 10px;">
                            <option value=""><?php echo get_phrase('all_classes');?></option>
                            <?php getFullClassList(); ?>
                        </select>
                    </div>
                    <div class="flex gap-3" id="action-buttons" style="display: none;">
                        <button onclick="DriveListManager.print()" class="btn btn-info" style="padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 15px;">
                            <i class="fa fa-print"></i> <?php echo get_phrase('print'); ?>
                        </button>
                        <a id="excel-export-btn" href="#" class="btn btn-success" style="padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 15px;">
                            <i class="fa fa-file-excel"></i> Excel
                        </a>
                        <a id="pdf-export-btn" href="#" class="btn btn-danger" style="padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 15px;">
                            <i class="fa fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
            <div id="drive-list-content" class="mt-6" style="max-height: 600px; overflow-y: auto;"></div>
        </div>
    </div>
</div>

<script>
/**
 * Enterprise-Grade Drive List Manager
 * Handles all drive list operations with proper state management and backdrop control
 */
var DriveListManager = (function() {
    'use strict';
    
    // Private state
    var state = {
        currentType: null,
        isLoading: false,
        baseUrl: '<?php echo site_url('admin/'); ?>'
    };
    
    // Private methods
    
    function updateActiveCard(type) {
        $('.fee-card').removeClass('active');
        $('.fee-card[data-type="' + type + '"]').addClass('active');
    }
    
    function showLoading() {
        $('#drive-list-content').html(
            '<div class="text-center py-8">' +
            '<i class="fa fa-spinner fa-spin text-4xl text-blue-600"></i>' +
            '<p class="mt-4 text-gray-600"><?php echo get_phrase('loading');?>...</p>' +
            '</div>'
        );
    }
    
    function showError(message) {
        $('#drive-list-content').html(
            '<div class="text-center py-8 text-red-600">' +
            '<i class="fa fa-exclamation-circle text-4xl mb-3"></i>' +
            '<p>' + message + '</p>' +
            '</div>'
        );
    }
    
    function updateExportButtons(type, classFilter) {
        var baseExportUrl = state.baseUrl + 'export_drive_list/' + type;
        var queryString = classFilter ? '?class_filter=' + encodeURIComponent(classFilter) : '';
        
        $('#excel-export-btn').attr('href', baseExportUrl + '/excel' + queryString);
        $('#pdf-export-btn').attr('href', baseExportUrl + '/pdf' + queryString);
    }
    
    // Public API
    return {
        init: function() {
            // Setup class filter change handler
            $('#class-filter').on('change', function() {
                if (state.currentType && !state.isLoading) {
                    DriveListManager.load(state.currentType);
                }
            });
        },
        
        load: function(type) {
            if (state.isLoading) return;
            
            state.isLoading = true;
            state.currentType = type;
            
            var classFilter = $('#class-filter').val() || '';
            
            showLoading();
            $('#action-buttons').hide();
            updateActiveCard(type);
            
            $.ajax({
                url: state.baseUrl + 'get_drive_list/' + type + '?class_filter=' + encodeURIComponent(classFilter) + '&_=' + new Date().getTime(),
                type: 'GET',
                dataType: 'json',
                cache: false,
                timeout: 30000
            })
            .done(function(response) {
                if (response && response.status === 'success') {
                    $('#drive-list-content').html(response.html || '<p class="text-center py-8">No data available</p>');
                    
                    if (response.has_data) {
                        updateExportButtons(type, classFilter);
                        $('#action-buttons').show();
                    } else {
                        $('#action-buttons').hide();
                    }
                } else {
                    showError(response.message || 'Failed to load data');
                    $('#action-buttons').hide();
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Drive list load error:', status, error);
                showError('<?php echo get_phrase('an_error_occurred');?>');
            })
            .always(function() {
                state.isLoading = false;
            });
        },
        
        print: function() {
            if (!state.currentType) return;
            
            var classFilter = $('#class-filter').val() || '';
            var printUrl = state.baseUrl + 'export_drive_list/' + state.currentType + '/print';
            if (classFilter) {
                printUrl += '?class_filter=' + encodeURIComponent(classFilter);
            }
            window.open(printUrl, '_blank');
        }
    };
})();

// Initialize on document ready
$(document).ready(function() {
    DriveListManager.init();
});
</script>
