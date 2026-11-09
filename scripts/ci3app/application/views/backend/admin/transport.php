<hr />
<div class="row">
	<div class="col-md-12">
    
    	<!---CONTROL TABS START-->
		<ul class="nav nav-tabs bordered">
			<li class="active">
            	<a href="#routes" data-toggle="tab"><i class="entypo-direction"></i> 
					<?php echo get_phrase('routes');?>
                    	</a></li>
			<li>
            	<a href="#vehicles" data-toggle="tab"><i class="entypo-traffic-cone"></i>
					<?php echo get_phrase('vehicles');?>
                    	</a></li>
			<li>
            	<a href="#drivers" data-toggle="tab"><i class="entypo-user"></i>
					<?php echo get_phrase('drivers');?>
                    	</a></li>
			<li>
            	<a href="#attendance" data-toggle="tab"><i class="entypo-check"></i>
					<?php echo get_phrase('attendance');?>
                    	</a></li>
		</ul>
    	<!---CONTROL TABS END-->
        
	
		<div class="tab-content">
        <br>
            <?php $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?>
            
            <!-- ROUTES TAB -->
            <div class="tab-pane box active" id="routes">
                <div class="box-header">
                    <button class="btn btn-primary pull-right" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_add_route');?>');">
                        <i class="entypo-plus"></i> <?php echo get_phrase('add_route');?>
                    </button>
                </div>
                <table class="table table-bordered datatable" id="table_export">
                	<thead>
                		<tr>
                    		<th><?php echo get_phrase('route_name');?></th>
                    		<th><?php echo get_phrase('route_code');?></th>
                    		<th><?php echo get_phrase('stops');?></th>
                    		<th><?php echo get_phrase('fare');?></th>
                    		<th><?php echo get_phrase('status');?></th>
                    		<th><?php echo get_phrase('options');?></th>
						</tr>
					</thead>
                    <tbody>
                    	<?php foreach($transports as $row):?>
                        <tr>
							<td><?php echo $row['route_name'];?></td>
							<td><span class="badge badge-info"><?php echo isset($row['route_code']) ? $row['route_code'] : 'RT-'.str_pad($row['transport_id'], 3, '0', STR_PAD_LEFT);?></span></td>
							<td><?php echo isset($row['total_stops']) ? $row['total_stops'] : 0;?> stops</td>
							<td><?php echo $currency.$row['route_fare'];?></td>
							<td><span class="label label-success">Active</span></td>
							<td>
                            <div class="btn-group">
                                <?=get_action_button();?>
                                <ul class="dropdown-menu dropdown-default pull-right">
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_route_stops/'.$row['transport_id']);?>');">
                                            <i class="entypo-location"></i> <?php echo get_phrase('manage_stops');?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_transport_student/'.$row['transport_id']);?>');">
                                            <i class="entypo-users"></i> <?php echo get_phrase('students');?>
                                        </a>
                                    </li>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_edit_transport/'.$row['transport_id']);?>');">
                                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit');?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="confirm_modal('<?php echo site_url('admin/transport/delete/'.$row['transport_id']);?>');">
                                            <i class="entypo-trash"></i> <?php echo get_phrase('delete');?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
        					</td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
			</div>
            
            <!-- VEHICLES TAB -->
			<div class="tab-pane box" id="vehicles">
                <div class="box-header">
                    <button class="btn btn-primary pull-right" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_add_vehicle');?>');">
                        <i class="entypo-plus"></i> <?php echo get_phrase('add_vehicle');?>
                    </button>
                </div>
                <table class="table table-bordered datatable">
                	<thead>
                		<tr>
                    		<th><?php echo get_phrase('vehicle_number');?></th>
                    		<th><?php echo get_phrase('type');?></th>
                    		<th><?php echo get_phrase('capacity');?></th>
                    		<th><?php echo get_phrase('driver');?></th>
                    		<th><?php echo get_phrase('route');?></th>
                    		<th><?php echo get_phrase('status');?></th>
                    		<th><?php echo get_phrase('options');?></th>
						</tr>
					</thead>
                    <tbody>
                    	<?php if(isset($vehicles)): foreach($vehicles as $vehicle):?>
                        <tr>
							<td><strong><?php echo $vehicle['vehicle_number'];?></strong></td>
							<td><?php echo isset($vehicle['vehicle_type']) ? $vehicle['vehicle_type'] : 'N/A';?></td>
							<td><?php echo isset($vehicle['seating_capacity']) ? $vehicle['seating_capacity'] : 0;?> seats</td>
							<td><?php echo isset($vehicle['driver_name']) ? $vehicle['driver_name'] : 'Not Assigned';?></td>
							<td><?php echo isset($vehicle['assigned_route']) ? $vehicle['assigned_route'] : 'Not Assigned';?></td>
							<td><span class="label label-success">Active</span></td>
							<td>
                            <div class="btn-group">
                                <?=get_action_button();?>
                                <ul class="dropdown-menu dropdown-default pull-right">
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_edit_vehicle/'.$vehicle['vehicle_id']);?>');">
                                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit');?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="confirm_modal('<?php echo site_url('admin/transport/delete_vehicle/'.$vehicle['vehicle_id']);?>');">
                                            <i class="entypo-trash"></i> <?php echo get_phrase('delete');?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
        					</td>
                        </tr>
                        <?php endforeach; endif;?>
                    </tbody>
                </table>
			</div>
            
            <!-- DRIVERS TAB -->
			<div class="tab-pane box" id="drivers">
                <div class="box-header">
                    <button class="btn btn-primary pull-right" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_add_driver');?>');">
                        <i class="entypo-plus"></i> <?php echo get_phrase('add_driver');?>
                    </button>
                </div>
                <table class="table table-bordered datatable">
                	<thead>
                		<tr>
                    		<th><?php echo get_phrase('name');?></th>
                    		<th><?php echo get_phrase('phone');?></th>
                    		<th><?php echo get_phrase('license_number');?></th>
                    		<th><?php echo get_phrase('assigned_vehicle');?></th>
                    		<th><?php echo get_phrase('status');?></th>
                    		<th><?php echo get_phrase('options');?></th>
						</tr>
					</thead>
                    <tbody>
                    	<?php if(isset($drivers)): foreach($drivers as $driver):?>
                        <tr>
							<td><?php echo $driver['name'];?></td>
							<td><?php echo $driver['phone'];?></td>
							<td><?php echo isset($driver['license_number']) ? $driver['license_number'] : 'N/A';?></td>
							<td><?php echo isset($driver['vehicle_number']) ? $driver['vehicle_number'] : 'Not Assigned';?></td>
							<td><span class="label label-success">Active</span></td>
							<td>
                            <div class="btn-group">
                                <?=get_action_button();?>
                                <ul class="dropdown-menu dropdown-default pull-right">
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_edit_driver/'.$driver['driver_id']);?>');">
                                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit');?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="confirm_modal('<?php echo site_url('admin/transport/delete_driver/'.$driver['driver_id']);?>');">
                                            <i class="entypo-trash"></i> <?php echo get_phrase('delete');?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
        					</td>
                        </tr>
                        <?php endforeach; endif;?>
                    </tbody>
                </table>
			</div>
            
            <!-- ATTENDANCE TAB -->
			<div class="tab-pane box" id="attendance">
                <div class="form-inline" style="margin-bottom: 20px;">
                    <label><?php echo get_phrase('date');?>:</label>
                    <input type="text" class="form-control datepicker" id="attendance_date" value="<?php echo date('Y-m-d');?>"/>
                    <label><?php echo get_phrase('route');?>:</label>
                    <select class="form-control" id="attendance_route">
                        <option value=""><?php echo get_phrase('all_routes');?></option>
                        <?php foreach($transports as $route):?>
                        <option value="<?php echo $route['transport_id'];?>"><?php echo $route['route_name'];?></option>
                        <?php endforeach;?>
                    </select>
                    <button class="btn btn-info" onclick="loadAttendance();"><i class="entypo-search"></i> <?php echo get_phrase('search');?></button>
                </div>
                <div id="attendance_data">
                    <p class="text-muted"><?php echo get_phrase('select_date_and_route_to_view_attendance');?></p>
                </div>
			</div>
            
		</div>
	</div>
</div>

<script>
function loadAttendance() {
    var date = $('#attendance_date').val();
    var route = $('#attendance_route').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/transport/get_attendance');?>',
        type: 'POST',
        data: {date: date, route_id: route},
        success: function(response) {
            $('#attendance_data').html(response);
        }
    });
}
</script>
