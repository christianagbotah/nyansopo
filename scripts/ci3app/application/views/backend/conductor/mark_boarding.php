<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <?php echo get_phrase('mark_bus_boarding'); ?>
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?php echo get_phrase('select_route'); ?></label>
                            <select class="form-control" id="route_id" onchange="loadRouteStudents()">
                                <option value=""><?php echo get_phrase('select_route'); ?></option>
                                <?php
                                $routes = $this->db->get_where('transport_routes', ['status' => 'active'])->result_array();
                                foreach ($routes as $route):
                                ?>
                                <option value="<?php echo $route['route_id']; ?>">
                                    <?php echo $route['route_name'] . ' - GHS ' . number_format($route['route_fare'], 2); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?php echo get_phrase('direction'); ?></label>
                            <select class="form-control" id="boarding_direction">
                                <option value="in"><?php echo get_phrase('morning_in'); ?> (<?php echo get_phrase('to_school'); ?>)</option>
                                <option value="out"><?php echo get_phrase('afternoon_out'); ?> (<?php echo get_phrase('from_school'); ?>)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div id="students_list" style="display:none;">
                    <h4><?php echo get_phrase('students_on_route'); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('student_code'); ?></th>
                                    <th><?php echo get_phrase('name'); ?></th>
                                    <th><?php echo get_phrase('class'); ?></th>
                                    <th><?php echo get_phrase('payment_status'); ?></th>
                                    <th><?php echo get_phrase('action'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="students_tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadRouteStudents() {
    var routeId = $('#route_id').val();
    if (!routeId) {
        $('#students_list').hide();
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    var direction = $('#boarding_direction').val();
    
    $.get('<?php echo site_url('daily_transport/get_route_students_with_payment/'); ?>' + routeId + '/' + direction, function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (data.status === 'success') {
            var html = '';
            data.students.forEach(function(student) {
                var paymentBadge = '';
                var btnClass = 'btn-success';
                var warning = '';
                
                if (direction === 'out') {
                    if (student.out_paid) {
                        paymentBadge = '<span class="badge badge-success">PAID</span>';
                    } else {
                        paymentBadge = '<span class="badge badge-danger">UNPAID</span>';
                        warning = ' <small class="text-danger">(Will be billed as owing)</small>';
                        btnClass = 'btn-warning';
                    }
                } else {
                    if (student.in_paid) {
                        paymentBadge = '<span class="badge badge-success">PAID</span>';
                    } else {
                        paymentBadge = '<span class="badge badge-danger">UNPAID</span>';
                    }
                }
                
                html += '<tr id="row_' + student.student_id + '">';
                html += '<td>' + student.student_code + '</td>';
                html += '<td>' + student.name + warning + '</td>';
                html += '<td>' + student.class_name + '</td>';
                html += '<td>' + paymentBadge + '</td>';
                html += '<td><button class="btn btn-sm ' + btnClass + '" onclick="markBoarding(' + student.student_id + ')">';
                html += '<i class="entypo-check"></i> <?php echo get_phrase('mark_boarded'); ?></button></td>';
                html += '</tr>';
            });
            
            $('#students_tbody').html(html);
            $('#students_list').show();
            $('.close')[0].click();
        } else {
            showAjaxModal_alert(data.message || '<?php echo get_phrase('error_loading_students'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
}

function markBoarding(studentId) {
    var routeId = $('#route_id').val();
    var direction = $('#boarding_direction').val();
    
    showAjaxModal_alert('<?php echo get_phrase('marking'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('daily_transport/mark_boarding'); ?>',
        type: 'POST',
        data: {
            student_id: studentId,
            route_id: routeId,
            direction: direction
        },
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            $('#row_' + studentId).find('button').removeClass('btn-success').addClass('btn-default').prop('disabled', true)
                .html('<i class="entypo-check"></i> <?php echo get_phrase('boarded'); ?>');
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
}
</script>
