<?php
$houses = $this->db->get('boarding_house')->result_array();
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="bg-light">
            <tr>
                <th>House Name</th>
                <th>Total Beds</th>
                <th>Occupied</th>
                <th>Available</th>
                <th>Occupancy Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($houses as $house): 
                $house_dorms = $this->db->get_where('boarding_dormitory', ['house_id' => $house['house_id']])->result_array();
                $total_beds = 0;
                $occupied_beds = 0;
                foreach($house_dorms as $dorm) {
                    $total_beds += $this->db->where('dormitory_id', $dorm['dormitory_id'])->from('boarding_bed')->count_all_results();
                    $occupied_beds += $this->db->where('dormitory_id', $dorm['dormitory_id'])->where('bed_status', 'occupied')->from('boarding_bed')->count_all_results();
                }
                $occupancy_rate = $total_beds > 0 ? round(($occupied_beds / $total_beds) * 100, 2) : 0;
            ?>
            <tr>
                <td><strong><?php echo $house['house_name']; ?></strong></td>
                <td><?php echo $total_beds; ?></td>
                <td><?php echo $occupied_beds; ?></td>
                <td><?php echo $total_beds - $occupied_beds; ?></td>
                <td><span class="badge badge-<?php echo $occupancy_rate > 80 ? 'danger' : ($occupancy_rate > 50 ? 'warning' : 'success'); ?>"><?php echo $occupancy_rate; ?>%</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
