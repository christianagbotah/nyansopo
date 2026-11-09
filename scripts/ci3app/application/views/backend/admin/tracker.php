<?php
	$visitors = $this->db->get('visitor_tracker')->result_array();
?>
<div class="row">
	<table class="table table-bordered table-hover table-striped" id="dt">
		<thead>
			<tr>
				<th>S/N</th>
				<th>IP Address</th>
				<th>Page</th>
				<th>Time</th>
			</tr>
		</thead>
		<tbody>
			<?php 
				$i = 1;
				foreach($visitors as $row):
			?>
			<tr>
				<td><?= $i; ?></td>
				<td><?= $row['ip']; ?></td>
				<td><?= $row['page_view']; ?></td>
				<td><?= date('M d, Y H:i:s',$row['date']); ?></td>
			</tr>

		<?php 
		$i++;
	endforeach; ?>
		</tbody>
	</table>
</div>

<script>
    $(function() {
       $('#dt').dataTable(); 
    });
</script>