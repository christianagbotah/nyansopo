<?php
$house = $this->db->get_where('boarding_houses', array('house_id' => $param2))->row();
?>
<div class="modal-content">
    <div class="modal-header">
        <h3>Edit Boarding House</h3>
    </div>
    <div class="modal-body">
        <?php echo form_open_multipart(site_url('admin/boarding/update_house/'.$house->house_id), array('id' => 'edit_house_form')); ?>
            <div class="form-group">
                <label>House Name</label>
                <input type="text" name="house_name" class="form-control" value="<?php echo $house->house_name; ?>" required>
            </div>
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="house_capacity" class="form-control" value="<?php echo $house->house_capacity; ?>" required>
            </div>
            <div class="form-group">
                <label>Fee</label>
                <input type="number" step="0.01" name="house_fee" class="form-control" value="<?php echo $house->house_fee; ?>">
            </div>
            <div class="form-group">
                <label>House Master</label>
                <select name="house_master_id" class="form-control">
                    <option value="">Select Teacher</option>
                    <?php
                    $teachers = $this->db->get('teacher')->result_array();
                    foreach($teachers as $teacher) {
                        $selected = ($teacher['teacher_id'] == $house->house_master_id) ? 'selected' : '';
                        echo '<option value="'.$teacher['teacher_id'].'" '.$selected.'>'.$teacher['name'].'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active" <?php if($house->status == 'Active') echo 'selected'; ?>>Active</option>
                    <option value="Inactive" <?php if($house->status == 'Inactive') echo 'selected'; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update House</button>
        </form>
    </div>
</div>

<script>
$('#edit_house_form').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: new FormData(this),
        processData: false,
        contentType: false,
        success: function() {
            $('.close').click();
            loadHouses();
            loadStats();
        }
    });
});
</script>
