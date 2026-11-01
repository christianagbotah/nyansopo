<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <?php echo get_phrase('morning_transport_collection'); ?>
                    <span class="pull-right"><?php echo date('l, d M Y'); ?></span>
                </div>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="entypo-info"></i> <strong><?php echo get_phrase('note'); ?>:</strong> 
                    <?php echo get_phrase('collect_all_transport_fares_in_the_morning'); ?>. 
                    <?php echo get_phrase('if_child_boards_both_ways_collect_full_amount_now'); ?>.
                </div>
                
                <div class="form-group">
                    <label><?php echo get_phrase('select_student'); ?></label>
                    <select class="form-control select2" id="student_id" onchange="loadStudentTransport()">
                        <option value=""><?php echo get_phrase('select_student'); ?></option>
                        <?php
                        $students = $this->db->query("
                            SELECT s.student_id, s.name, s.student_code, s.transport_id,
                                   c.name as class_name
                            FROM student s
                            JOIN enroll e ON s.student_id = e.student_id
                            JOIN class c ON e.class_id = c.class_id
                            WHERE s.transport_id IS NOT NULL
                            AND e.year = ?
                            AND e.term = ?
                            AND e.mute = 0
                            ORDER BY s.name
                        ", [get_settings('running_year'), get_settings('running_term')])->result_array();
                        
                        foreach ($students as $student):
                        ?>
                        <option value="<?php echo $student['student_id']; ?>" data-route="<?php echo $student['transport_id']; ?>">
                            <?php echo $student['student_code'] . ' - ' . $student['name'] . ' (' . $student['class_name'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="transport_form" style="display:none;">
                    <form id="transportForm">
                        <input type="hidden" name="student_id" id="form_student_id">
                        <input type="hidden" name="choice_date" value="<?php echo date('Y-m-d'); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo get_phrase('route'); ?></label>
                                    <input type="text" class="form-control" id="route_name" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo get_phrase('route_fare'); ?></label>
                                    <input type="text" class="form-control" id="route_fare" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo get_phrase('transport_usage_today'); ?> <span class="text-danger">*</span></label>
                            <select class="form-control" name="transport_direction" id="transport_direction" required onchange="calculateFare()">
                                <option value=""><?php echo get_phrase('select'); ?></option>
                                <option value="none"><?php echo get_phrase('not_using_transport_today'); ?></option>
                                <option value="in"><?php echo get_phrase('morning_only'); ?> (<?php echo get_phrase('to_school'); ?>)</option>
                                <option value="out"><?php echo get_phrase('afternoon_only'); ?> (<?php echo get_phrase('from_school'); ?>)</option>
                                <option value="both"><?php echo get_phrase('both_ways'); ?> (<?php echo get_phrase('to_and_from_school'); ?>)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo get_phrase('amount_to_collect'); ?></label>
                            <input type="text" class="form-control" id="amount_display" readonly style="font-size:20px; font-weight:bold; color:green;">
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo get_phrase('notes'); ?></label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="<?php echo get_phrase('optional_reason_or_note'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="entypo-check"></i> <?php echo get_phrase('collect_payment'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var routeFare = 0;

function loadStudentTransport() {
    var studentId = $('#student_id').val();
    if (!studentId) {
        $('#transport_form').hide();
        return;
    }
    
    var routeId = $('#student_id option:selected').data('route');
    
    $.get('<?php echo site_url('admin/get_route_info/'); ?>' + routeId, function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (data.status === 'success') {
            $('#form_student_id').val(studentId);
            $('#route_name').val(data.route.route_name);
            $('#route_fare').val('GHS ' + parseFloat(data.route.route_fare).toFixed(2));
            routeFare = parseFloat(data.route.route_fare);
            $('#transport_form').show();
            $('#transport_direction').val('').trigger('change');
        }
    });
}

function calculateFare() {
    var direction = $('#transport_direction').val();
    var amount = 0;
    
    if (direction == 'in' || direction == 'out') {
        amount = routeFare;
    } else if (direction == 'both') {
        amount = routeFare * 2;
    }
    
    if (amount > 0) {
        $('#amount_display').val('GHS ' + amount.toFixed(2));
    } else {
        $('#amount_display').val('GHS 0.00');
    }
}

$('#transportForm').submit(function(e) {
    e.preventDefault();
    
    var direction = $('#transport_direction').val();
    if (!direction) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_transport_usage'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('processing'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('daily_transport/set_choice_and_collect'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => {
                $('#transportForm')[0].reset();
                $('#student_id').val('').trigger('change');
                $('#transport_form').hide();
            }, 2000);
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});

$(document).ready(function() {
    $('.select2').select2();
});
</script>
