<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('students_using_transport'); ?> - <?php echo $transport->route_name; ?></h3>
			</div>
			<div class="panel-body">
				<div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
					<a href="<?php echo site_url('admin/transport'); ?>" class="btn btn-default" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'">
						<i class="entypo-left-open-big"></i> <?php echo get_phrase('back'); ?>
					</a>
					<div style="display: flex; gap: 10px;">
						<button onclick="printTable()" class="btn btn-info" style="padding: 10px 20px; border-radius: 8px; font-weight: 600;">
							<i class="entypo-print"></i> <?php echo get_phrase('print'); ?>
						</button>
						<a href="<?php echo site_url('admin/export_transport_students/'.$transport_id.'/excel'); ?>" class="btn btn-success" style="padding: 10px 20px; border-radius: 8px; font-weight: 600;">
							<i class="entypo-download"></i> Excel
						</a>
						<a href="<?php echo site_url('admin/export_transport_students/'.$transport_id.'/pdf'); ?>" class="btn btn-danger" style="padding: 10px 20px; border-radius: 8px; font-weight: 600;">
							<i class="entypo-doc-text"></i> PDF
						</a>
					</div>
				</div>
				<style>
					#transport_students_table {
						border-collapse: separate;
						border-spacing: 0;
						border-radius: 12px;
						overflow: hidden;
						box-shadow: 0 4px 6px rgba(0,0,0,0.05);
					}
					#transport_students_table thead {
						background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					}
					#transport_students_table thead th {
						color: white;
						font-weight: 600;
						text-transform: uppercase;
						font-size: 12px;
						letter-spacing: 0.5px;
						padding: 16px 20px;
						border: none;
					}
					#transport_students_table thead th:last-child {
						text-align: right;
					}
					#transport_students_table tbody tr {
						transition: all 0.3s ease;
						border-bottom: 1px solid #f0f0f0;
					}
					#transport_students_table tbody tr:hover {
						background: linear-gradient(135deg, #667eea05 0%, #764ba205 100%);
						transform: scale(1.01);
						box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
					}
					#transport_students_table tbody td {
						padding: 16px 20px;
						vertical-align: middle;
						color: #2c3e50;
						font-size: 14px;
						border: none;
					}
					#transport_students_table tbody td:first-child {
						font-weight: 600;
						color: #667eea;
					}
					#transport_students_table tbody td:last-child {
						text-align: right;
					}
					.dataTables_wrapper .dataTables_length,
					.dataTables_wrapper .dataTables_filter {
						margin-bottom: 20px;
					}
					.dataTables_wrapper .dataTables_filter input {
						border: 2px solid #e0e0e0;
						border-radius: 8px;
						padding: 8px 16px;
						transition: all 0.3s ease;
					}
					.dataTables_wrapper .dataTables_filter input:focus {
						border-color: #667eea;
						outline: none;
						box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
					}
					.dataTables_wrapper .dataTables_paginate .paginate_button {
						border-radius: 6px;
						margin: 0 2px;
						transition: all 0.3s ease;
					}
					.dataTables_wrapper .dataTables_paginate .paginate_button.current {
						background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
						color: white !important;
						border: none !important;
					}
					.dataTables_wrapper .dataTables_info {
						color: #7f8c8d;
						font-size: 14px;
					}
				</style>
				<table id="transport_students_table" class="table">
					<thead>
						<tr>
							<th><?php echo get_phrase('student_code'); ?></th>
							<th><?php echo get_phrase('name'); ?></th>
							<th><?php echo get_phrase('class'); ?></th>
							<th><?php echo get_phrase('guardian_phone'); ?></th>
							<th><?php echo get_phrase('actions'); ?></th>
						</tr>
					</thead>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	$('#transport_students_table').DataTable({
		"processing": true,
		"serverSide": false,
		"ajax": "<?php echo site_url('admin/get_transport_students/'.$transport_id); ?>",
		"columns": [
			{ "data": "student_code" },
			{ "data": "name" },
			{ "data": "class" },
			{ "data": "guardian_phone" },
			{
				"data": null,
				"orderable": false,
				"className": "text-right",
				"render": function(data, type, row) {
					return '<div style="display: flex; gap: 8px; justify-content: flex-end;">' +
						'<button onclick="reassignStudent(' + row.student_id + ', \'' + row.name + '\')" class="btn btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 500; transition: all 0.3s ease;" onmouseover="this.style.opacity=\'0.9\'" onmouseout="this.style.opacity=\'1\'" title="Reassign to another route">' +
							'<i class="entypo-shuffle"></i> Reassign' +
						'</button>' +
						'<button onclick="unassignStudent(' + row.student_id + ', \'' + row.name + '\')" class="btn btn-sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 500; transition: all 0.3s ease;" onmouseover="this.style.opacity=\'0.9\'" onmouseout="this.style.opacity=\'1\'" title="Remove from this route">' +
							'<i class="entypo-cancel"></i> Unassign' +
						'</button>' +
					'</div>';
				}
			}
		],
		"dom": 'lrtip'
	});
});

function printTable() {
	var printWindow = window.open('', '', 'height=600,width=800');
	printWindow.document.write('<html><head><title>Transport Students - <?php echo $transport->route_name; ?></title>');
	printWindow.document.write('<style>');
	printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
	printWindow.document.write('h2 { color: #667eea; margin-bottom: 20px; }');
	printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
	printWindow.document.write('th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }');
	printWindow.document.write('th { background-color: #667eea; color: white; font-weight: bold; }');
	printWindow.document.write('tr:nth-child(even) { background-color: #f9f9f9; }');
	printWindow.document.write('@media print { button { display: none; } }');
	printWindow.document.write('</style></head><body>');
	printWindow.document.write('<h2>Transport Students - <?php echo $transport->route_name; ?></h2>');
	printWindow.document.write('<p><strong>Date:</strong> ' + new Date().toLocaleDateString() + '</p>');
	printWindow.document.write('<table>');
	printWindow.document.write('<thead><tr><th>Student Code</th><th>Name</th><th>Class</th><th>Guardian Phone</th></tr></thead>');
	printWindow.document.write('<tbody>');
	
	var table = $('#transport_students_table').DataTable();
	var data = table.rows().data();
	
	for (var i = 0; i < data.length; i++) {
		printWindow.document.write('<tr>');
		printWindow.document.write('<td>' + data[i].student_code + '</td>');
		printWindow.document.write('<td>' + data[i].name + '</td>');
		printWindow.document.write('<td>' + data[i].class + '</td>');
		printWindow.document.write('<td>' + data[i].guardian_phone + '</td>');
		printWindow.document.write('</tr>');
	}
	
	printWindow.document.write('</tbody></table>');
	printWindow.document.write('<div style="margin-top: 30px; text-align: center;">');
	printWindow.document.write('<button onclick="window.print()" style="padding: 10px 30px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Print</button>');
	printWindow.document.write('</div>');
	printWindow.document.write('</body></html>');
	printWindow.document.close();
}

function unassignStudent(studentId, studentName) {
	showConfirmModal(
		'<?php echo get_phrase('unassign_student'); ?>',
		'Are you sure you want to unassign ' + studentName + ' from this transport route?',
		function() {
			showAjaxModal_alert('Unassigning...', 'loading');
			$.ajax({
				url: '<?php echo site_url('admin/transport_student_unassign/' . $transport_id . '/'); ?>' + studentId,
				type: 'POST',
				dataType: 'json'
			}).done(function(response) {
				if(response.status === 'success') {
					showAjaxModal_alert(response.message, 'success');
					setTimeout(() => $('#transport_students_table').DataTable().ajax.reload(), 2000);
				} else {
					showAjaxModal_alert(response.message || 'Operation failed', 'error');
				}
			}).fail(function() {
				showAjaxModal_alert('An error occurred', 'error');
			});
		},
		'<?php echo get_phrase('unassign'); ?>',
		'danger'
	);
}

function reassignStudent(studentId, studentName) {
	showReassignModal(studentId, studentName);
}

function showReassignModal(studentId, studentName) {
	var modalHtml = '<div class="modal fade" id="reassign_modal" tabindex="-1" role="dialog">' +
		'<div class="modal-dialog" role="document">' +
		'<div class="modal-content">' +
		'<div class="modal-header">' +
		'<button type="button" class="close" data-dismiss="modal">&times;</button>' +
		'<h4 class="modal-title"><i class="entypo-shuffle" style="color: #3498db;"></i> <?php echo get_phrase('reassign_student'); ?></h4>' +
		'</div>' +
		'<div class="modal-body">' +
		'<p><strong>' + studentName + '</strong></p>' +
		'<div class="form-group">' +
		'<label><?php echo get_phrase('select_new_route'); ?></label>' +
		'<select id="new_transport_id" class="form-control">' +
		'<option value="">-- <?php echo get_phrase('select_route'); ?> --</option>' +
		'<?php $routes = $this->db->get('transport')->result(); foreach($routes as $route): ?>' +
		'<option value="<?php echo $route->transport_id; ?>"><?php echo $route->route_name; ?></option>' +
		'<?php endforeach; ?>' +
		'</select>' +
		'</div>' +
		'</div>' +
		'<div class="modal-footer" style="border-top: none; padding-top: 0; display: flex; gap: 12px; justify-content: flex-end;">' +
		'<button type="button" class="btn btn-default" data-dismiss="modal" style="padding: 10px 24px; border-radius: 8px; font-weight: 600; border: 2px solid #e0e0e0; transition: all 0.3s ease;" onmouseover="this.style.borderColor=\'#bdbdbd\'" onmouseout="this.style.borderColor=\'#e0e0e0\'"><?php echo get_phrase('cancel'); ?></button>' +
		'<button type="button" class="btn btn-primary" onclick="confirmReassign(' + studentId + ')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 12px rgba(102, 126, 234, 0.5)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 6px rgba(102, 126, 234, 0.4)\'"><?php echo get_phrase('reassign'); ?></button>' +
		'</div>' +
		'</div>' +
		'</div>' +
		'</div>';
	
	$('#reassign_modal').remove();
	$('body').append(modalHtml);
	$('#reassign_modal').modal('show');
}

function confirmReassign(studentId) {
	var newTransportId = $('#new_transport_id').val();
	
	if (!newTransportId) {
		showAjaxModal_alert('Please select a route', 'error');
		return;
	}
	
	$('#reassign_modal').modal('hide');
	showAjaxModal_alert('Reassigning...', 'loading');
	
	$.ajax({
		url: '<?php echo site_url('admin/transport_student_reassign/' . $transport_id . '/'); ?>' + studentId + '/' + newTransportId,
		type: 'POST',
		dataType: 'json'
	}).done(function(response) {
		if(response.status === 'success') {
			showAjaxModal_alert(response.message, 'success');
			setTimeout(() => $('#transport_students_table').DataTable().ajax.reload(), 2000);
		} else {
			showAjaxModal_alert(response.message || 'Operation failed', 'error');
		}
	}).fail(function() {
			showAjaxModal_alert('An error occurred', 'error');
	});
}
</script>

