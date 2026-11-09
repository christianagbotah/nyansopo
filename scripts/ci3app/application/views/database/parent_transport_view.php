<!-- Parent Transport Dashboard View -->
<div class="row">
    <div class="col-md-12">
        <h3><?php echo get_phrase('my_child_transport');?></h3>
        <hr>
    </div>
</div>

<?php foreach($children as $child): ?>
    <?php 
    $transport = $this->db->select('tsa.*, tr.route_name, tr.route_code, ts.stop_name, ts.pickup_time, ts.dropoff_time, ts.landmark')
                          ->from('transport_student_assignment tsa')
                          ->join('transport_routes tr', 'tsa.route_id = tr.route_id')
                          ->join('transport_stops ts', 'tsa.stop_id = ts.stop_id')
                          ->where('tsa.student_id', $child['student_id'])
                          ->where('tsa.status', 'active')
                          ->get()->row_array();
    
    if($transport):
    ?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h4><?php echo $child['name'];?> - <?php echo $child['class_name'];?></h4>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <!-- Route Information -->
                        <div class="col-md-4">
                            <div class="info-box">
                                <h5><i class="entypo-direction"></i> <?php echo get_phrase('route_information');?></h5>
                                <table class="table table-condensed">
                                    <tr>
                                        <td><strong><?php echo get_phrase('route');?>:</strong></td>
                                        <td><?php echo $transport['route_name'];?> 
                                            <span class="badge badge-info"><?php echo $transport['route_code'];?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo get_phrase('stop');?>:</strong></td>
                                        <td><?php echo $transport['stop_name'];?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo get_phrase('landmark');?>:</strong></td>
                                        <td><?php echo $transport['landmark'];?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo get_phrase('pickup_time');?>:</strong></td>
                                        <td><span class="label label-success"><?php echo date('h:i A', strtotime($transport['pickup_time']));?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo get_phrase('dropoff_time');?>:</strong></td>
                                        <td><span class="label label-info"><?php echo date('h:i A', strtotime($transport['dropoff_time']));?></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Live Tracking -->
                        <div class="col-md-8">
                            <div class="info-box">
                                <h5><i class="entypo-location"></i> <?php echo get_phrase('live_bus_location');?></h5>
                                <div id="map_<?php echo $child['student_id'];?>" style="height: 300px; border: 1px solid #ddd;"></div>
                                <div style="margin-top: 10px;">
                                    <button class="btn btn-sm btn-primary" onclick="refreshLocation(<?php echo $child['student_id'];?>)">
                                        <i class="entypo-arrows-ccw"></i> <?php echo get_phrase('refresh');?>
                                    </button>
                                    <span id="eta_<?php echo $child['student_id'];?>" class="pull-right" style="padding: 5px;">
                                        <strong><?php echo get_phrase('estimated_arrival');?>:</strong> <span class="label label-warning">Calculating...</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 20px;">
                        <!-- Recent Attendance -->
                        <div class="col-md-6">
                            <div class="info-box">
                                <h5><i class="entypo-check"></i> <?php echo get_phrase('recent_attendance');?></h5>
                                <table class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th><?php echo get_phrase('date');?></th>
                                            <th><?php echo get_phrase('pickup');?></th>
                                            <th><?php echo get_phrase('dropoff');?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $attendance = $this->db->select('*')
                                                               ->from('transport_attendance')
                                                               ->where('student_id', $child['student_id'])
                                                               ->order_by('attendance_date', 'DESC')
                                                               ->limit(7)
                                                               ->get()->result_array();
                                        
                                        foreach($attendance as $att):
                                        ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($att['attendance_date']));?></td>
                                            <td>
                                                <?php if($att['pickup_status'] == 'present'):?>
                                                    <span class="label label-success">
                                                        <i class="entypo-check"></i> <?php echo $att['pickup_time'];?>
                                                    </span>
                                                <?php elseif($att['pickup_status'] == 'absent'):?>
                                                    <span class="label label-danger">
                                                        <i class="entypo-cancel"></i> Absent
                                                    </span>
                                                <?php else:?>
                                                    <span class="label label-default">Pending</span>
                                                <?php endif;?>
                                            </td>
                                            <td>
                                                <?php if($att['dropoff_status'] == 'present'):?>
                                                    <span class="label label-success">
                                                        <i class="entypo-check"></i> <?php echo $att['dropoff_time'];?>
                                                    </span>
                                                <?php elseif($att['dropoff_status'] == 'absent'):?>
                                                    <span class="label label-danger">
                                                        <i class="entypo-cancel"></i> Absent
                                                    </span>
                                                <?php else:?>
                                                    <span class="label label-default">Pending</span>
                                                <?php endif;?>
                                            </td>
                                        </tr>
                                        <?php endforeach;?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Payment Information -->
                        <div class="col-md-6">
                            <div class="info-box">
                                <h5><i class="entypo-credit-card"></i> <?php echo get_phrase('payment_information');?></h5>
                                <table class="table table-condensed">
                                    <tr>
                                        <td><strong><?php echo get_phrase('monthly_fee');?>:</strong></td>
                                        <td><?php echo $currency.$transport['monthly_fee'];?></td>
                                    </tr>
                                </table>
                                
                                <h6><?php echo get_phrase('recent_payments');?></h6>
                                <table class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th><?php echo get_phrase('month');?></th>
                                            <th><?php echo get_phrase('amount');?></th>
                                            <th><?php echo get_phrase('status');?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $payments = $this->db->select('*')
                                                             ->from('transport_payments')
                                                             ->where('student_id', $child['student_id'])
                                                             ->order_by('payment_date', 'DESC')
                                                             ->limit(3)
                                                             ->get()->result_array();
                                        
                                        foreach($payments as $payment):
                                        ?>
                                        <tr>
                                            <td><?php echo $payment['payment_month'];?></td>
                                            <td><?php echo $currency.$payment['amount'];?></td>
                                            <td>
                                                <?php if($payment['status'] == 'paid'):?>
                                                    <span class="label label-success">Paid</span>
                                                <?php elseif($payment['status'] == 'overdue'):?>
                                                    <span class="label label-danger">Overdue</span>
                                                <?php else:?>
                                                    <span class="label label-warning">Pending</span>
                                                <?php endif;?>
                                            </td>
                                        </tr>
                                        <?php endforeach;?>
                                    </tbody>
                                </table>
                                
                                <button class="btn btn-success btn-sm" onclick="makePayment(<?php echo $child['student_id'];?>)">
                                    <i class="entypo-credit-card"></i> <?php echo get_phrase('make_payment');?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Notifications -->
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12">
                            <div class="info-box">
                                <h5><i class="entypo-bell"></i> <?php echo get_phrase('recent_notifications');?></h5>
                                <div class="list-group">
                                    <?php 
                                    $notifications = $this->db->select('*')
                                                              ->from('transport_notifications')
                                                              ->where('student_id', $child['student_id'])
                                                              ->order_by('sent_at', 'DESC')
                                                              ->limit(5)
                                                              ->get()->result_array();
                                    
                                    foreach($notifications as $notif):
                                    ?>
                                    <div class="list-group-item <?php echo $notif['read_status'] == 'unread' ? 'list-group-item-info' : '';?>">
                                        <h5 class="list-group-item-heading">
                                            <?php 
                                            if($notif['notification_type'] == 'pickup') echo '<i class="entypo-upload"></i>';
                                            elseif($notif['notification_type'] == 'dropoff') echo '<i class="entypo-download"></i>';
                                            elseif($notif['notification_type'] == 'delay') echo '<i class="entypo-clock"></i>';
                                            elseif($notif['notification_type'] == 'emergency') echo '<i class="entypo-attention"></i>';
                                            ?>
                                            <?php echo $notif['title'];?>
                                            <small class="pull-right"><?php echo date('d M, h:i A', strtotime($notif['sent_at']));?></small>
                                        </h5>
                                        <p class="list-group-item-text"><?php echo $notif['message'];?></p>
                                    </div>
                                    <?php endforeach;?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Emergency Contact -->
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <strong><i class="entypo-phone"></i> <?php echo get_phrase('emergency_contact');?>:</strong>
                                <?php echo get_phrase('for_transport_emergencies_call');?>: <strong>+1234567890</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<script>
var maps = {};
var markers = {};

// Initialize maps for each child
<?php foreach($children as $child): ?>
    <?php 
    $transport = $this->db->select('tsa.route_id')
                          ->from('transport_student_assignment tsa')
                          ->where('tsa.student_id', $child['student_id'])
                          ->where('tsa.status', 'active')
                          ->get()->row();
    if($transport):
    ?>
    maps[<?php echo $child['student_id'];?>] = new google.maps.Map(document.getElementById('map_<?php echo $child['student_id'];?>'), {
        center: {lat: 0, lng: 0},
        zoom: 13
    });
    
    loadBusLocation(<?php echo $child['student_id'];?>, <?php echo $transport->route_id;?>);
    <?php endif; ?>
<?php endforeach; ?>

function loadBusLocation(studentId, routeId) {
    $.ajax({
        url: '<?php echo site_url('parent/transport/get_bus_location');?>',
        type: 'POST',
        data: {route_id: routeId},
        dataType: 'json',
        success: function(data) {
            if(data.latitude && data.longitude) {
                var position = {lat: parseFloat(data.latitude), lng: parseFloat(data.longitude)};
                
                if(markers[studentId]) {
                    markers[studentId].setPosition(position);
                } else {
                    markers[studentId] = new google.maps.Marker({
                        position: position,
                        map: maps[studentId],
                        title: data.vehicle_number,
                        icon: {
                            url: '<?php echo base_url();?>assets/images/bus-icon.png',
                            scaledSize: new google.maps.Size(40, 40)
                        }
                    });
                }
                
                maps[studentId].setCenter(position);
                
                // Update ETA
                if(data.eta) {
                    $('#eta_' + studentId + ' .label').removeClass('label-warning').addClass('label-success').text(data.eta + ' mins');
                }
            }
        }
    });
}

function refreshLocation(studentId) {
    var routeId = <?php echo isset($transport['route_id']) ? $transport['route_id'] : 0;?>;
    loadBusLocation(studentId, routeId);
}

// Auto-refresh every 30 seconds
setInterval(function() {
    <?php foreach($children as $child): ?>
        <?php 
        $transport = $this->db->select('tsa.route_id')
                              ->from('transport_student_assignment tsa')
                              ->where('tsa.student_id', $child['student_id'])
                              ->where('tsa.status', 'active')
                              ->get()->row();
        if($transport):
        ?>
        loadBusLocation(<?php echo $child['student_id'];?>, <?php echo $transport->route_id;?>);
        <?php endif; ?>
    <?php endforeach; ?>
}, 30000);

function makePayment(studentId) {
    showAjaxModal('<?php echo site_url('parent/transport/payment_modal/');?>' + studentId);
}
</script>

<style>
.info-box {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.info-box h5 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
}
</style>
