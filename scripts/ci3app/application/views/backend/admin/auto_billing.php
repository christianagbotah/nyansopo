<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 px-8 py-6 rounded-t-lg">
            <h2 class="text-3xl font-bold text-white"><?php echo get_phrase('auto_billing_management'); ?></h2>
        </div>
        
        <div class="p-8">
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-100 p-4 rounded">
                    <h3 class="font-bold text-blue-800"><?php echo get_phrase('today_billed'); ?></h3>
                    <p class="text-3xl font-bold text-blue-600" id="today_count">0</p>
                </div>
                <div class="bg-green-100 p-4 rounded">
                    <h3 class="font-bold text-green-800"><?php echo get_phrase('confirmed'); ?></h3>
                    <p class="text-3xl font-bold text-green-600" id="confirmed_count">0</p>
                </div>
                <div class="bg-red-100 p-4 rounded">
                    <h3 class="font-bold text-red-800"><?php echo get_phrase('reversed'); ?></h3>
                    <p class="text-3xl font-bold text-red-600" id="reversed_count">0</p>
                </div>
            </div>
            
            <div class="mb-4 flex gap-2">
                <button onclick="runDailyBilling()" class="bg-blue-500 text-white px-4 py-2 rounded">
                    <i class="fa fa-play"></i> <?php echo get_phrase('run_daily_billing'); ?>
                </button>
                <input type="date" id="billing_date" value="<?php echo date('Y-m-d'); ?>" class="border rounded px-3 py-2">
            </div>
            
            <table id="billing_table" class="w-full text-left text-gray-700">
                <thead class="text-sm text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th class="px-6 py-4"><?php echo get_phrase('date'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('student'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('route'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('amount'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('present'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('boarded'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('status'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadStats();
    var table = $('#billing_table').DataTable({
        ajax: '<?php echo site_url("admin/get_billing_records"); ?>',
        columns: [
            { data: 'billing_date', className: 'px-6 py-4' },
            { data: 'student_name', className: 'px-6 py-4' },
            { data: 'route_name', className: 'px-6 py-4' },
            { data: 'fare_amount', className: 'px-6 py-4' },
            { data: 'was_present', className: 'px-6 py-4' },
            { data: 'boarded_bus', className: 'px-6 py-4' },
            { 
                data: 'billing_status',
                className: 'px-6 py-4',
                render: function(data) {
                    var colors = {confirmed: 'green', pending: 'yellow', reversed: 'red'};
                    return '<span class="bg-' + colors[data] + '-200 text-' + colors[data] + '-800 px-2 py-1 rounded">' + data + '</span>';
                }
            }
        ]
    });
});

function loadStats() {
    $.get('<?php echo site_url("admin/get_billing_stats"); ?>', function(data) {
        $('#today_count').text(data.today);
        $('#confirmed_count').text(data.confirmed);
        $('#reversed_count').text(data.reversed);
    }, 'json');
}

function runDailyBilling() {
    var date = $('#billing_date').val();
    showAjaxModal_alert('<?php echo get_phrase('processing_billing'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("transport_auto_billing/process_daily_billing"); ?>/' + date,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(function() {
                    $('#billing_table').DataTable().ajax.reload();
                    loadStats();
                }, 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'error');
        }
    });
}
</script>
