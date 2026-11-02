<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-8 py-6 rounded-t-lg">
            <h2 class="text-3xl font-bold text-white"><?php echo get_phrase('daily_transport_log'); ?></h2>
            <p class="text-white mt-2"><?php echo date('l, F j, Y'); ?></p>
        </div>
        
        <div class="p-8">
            <div class="mb-4">
                <label class="font-bold"><?php echo get_phrase('select_route'); ?>:</label>
                <select id="route_filter" class="ml-2 border rounded px-3 py-2">
                    <option value=""><?php echo get_phrase('all_routes'); ?></option>
                    <?php foreach($routes as $route): ?>
                        <option value="<?php echo $route['transport_id']; ?>"><?php echo $route['route_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <table id="daily_log_table" class="w-full text-left text-gray-700">
                <thead class="text-sm text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th class="px-6 py-4"><?php echo get_phrase('student_code'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('name'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('class'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('route'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('boarded'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('paid_fare'); ?></th>
                        <th class="px-6 py-4"><?php echo get_phrase('action'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#daily_log_table').DataTable({
        ajax: {
            url: '<?php echo site_url("conductor/get_daily_students"); ?>',
            data: function(d) {
                d.route_id = $('#route_filter').val();
            }
        },
        columns: [
            { data: 'student_code', className: 'px-6 py-4' },
            { data: 'name', className: 'px-6 py-4' },
            { data: 'class', className: 'px-6 py-4' },
            { data: 'route_name', className: 'px-6 py-4' },
            { 
                data: 'boarded_bus',
                className: 'px-6 py-4',
                render: function(data, type, row) {
                    var checked = data == 'yes' ? 'checked' : '';
                    return '<input type="checkbox" class="boarded-check w-5 h-5" data-student="' + row.student_id + '" data-transport="' + row.transport_id + '" ' + checked + '>';
                }
            },
            { 
                data: 'paid_fare',
                className: 'px-6 py-4',
                render: function(data, type, row) {
                    var checked = data == 'yes' ? 'checked' : '';
                    return '<input type="checkbox" class="paid-check w-5 h-5" data-student="' + row.student_id + '" data-transport="' + row.transport_id + '" ' + checked + '>';
                }
            },
            {
                data: null,
                className: 'px-6 py-4',
                render: function(data) {
                    return '<button onclick="saveLog(' + data.student_id + ',' + data.transport_id + ')" class="bg-green-500 text-white px-3 py-1 rounded"><?php echo get_phrase('save'); ?></button>';
                }
            }
        ]
    });
    
    $('#route_filter').on('change', function() {
        table.ajax.reload();
    });
});

function saveLog(studentId, transportId) {
    var boarded = $('input.boarded-check[data-student="' + studentId + '"]').is(':checked') ? 'yes' : 'no';
    var paid = $('input.paid-check[data-student="' + studentId + '"]').is(':checked') ? 'yes' : 'no';
    
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("conductor/save_daily_log"); ?>',
        type: 'POST',
        data: {
            student_id: studentId,
            transport_id: transportId,
            boarded_bus: boarded,
            paid_fare: paid,
            log_date: '<?php echo date('Y-m-d'); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
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
