<?php
$bed_id = $this->uri->segment(4);
$edit_mode = !empty($bed_id);
$bed = $edit_mode ? $this->boarding_model->getBedById($bed_id) : [];
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-bed"></i> 
                    <?php echo $edit_mode ? get_phrase('edit_bed') : get_phrase('add_bed'); ?>
                </h3>
            </div>
            <div class="panel-body">
                
                <form action="<?php echo site_url('admin/manageDormitoryBed/' . ($edit_mode ? 'update/' . $bed_id : 'create')); ?>" 
                      method="post">
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('bed_code'); ?> *</label>
                        <input type="text" name="bed_code" class="form-control" 
                               value="<?php echo $edit_mode ? $bed['bed_code'] : ''; ?>" 
                               placeholder="e.g., A-001" required>
                        <small class="text-muted"><?php echo get_phrase('unique_bed_identifier'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('select_house'); ?> *</label>
                        <select id="house_select" class="form-control" required>
                            <option value="">-- <?php echo get_phrase('select'); ?> --</option>
                            <?php
                            $houses = $this->boarding_model->getAllHouses();
                            foreach($houses as $house):
                                $selected = false;
                                if($edit_mode) {
                                    $dorm = $this->boarding_model->getDormitoryById($bed['dormitory_id']);
                                    $selected = ($dorm['house_id'] == $house['house_id']);
                                }
                            ?>
                            <option value="<?php echo $house['house_id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                <?php echo $house['house_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('select_dormitory'); ?> *</label>
                        <select name="dormitory_id" id="dormitory_select" class="form-control" required>
                            <option value="">-- <?php echo get_phrase('select_house_first'); ?> --</option>
                            <?php if($edit_mode): 
                                $dormitories = $this->boarding_model->getDormitoriesByHouse($dorm['house_id']);
                                foreach($dormitories as $dormitory):
                            ?>
                            <option value="<?php echo $dormitory['dormitory_id']; ?>" 
                                    <?php echo ($bed['dormitory_id'] == $dormitory['dormitory_id']) ? 'selected' : ''; ?>>
                                <?php echo $dormitory['dormitory_name']; ?>
                            </option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    
                    <?php if($edit_mode): ?>
                    <div class="form-group">
                        <label><?php echo get_phrase('bed_status'); ?> *</label>
                        <select name="bed_status" class="form-control" required>
                            <option value="Available" <?php echo ($bed['bed_status'] == 'Available') ? 'selected' : ''; ?>>
                                <?php echo get_phrase('available'); ?>
                            </option>
                            <option value="Assigned" <?php echo ($bed['bed_status'] == 'Assigned') ? 'selected' : ''; ?>>
                                <?php echo get_phrase('assigned'); ?>
                            </option>
                            <option value="Maintenance" <?php echo ($bed['bed_status'] == 'Maintenance') ? 'selected' : ''; ?>>
                                <?php echo get_phrase('maintenance'); ?>
                            </option>
                            <option value="Reserved" <?php echo ($bed['bed_status'] == 'Reserved') ? 'selected' : ''; ?>>
                                <?php echo get_phrase('reserved'); ?>
                            </option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('notes'); ?></label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo $edit_mode ? $bed['notes'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group text-right">
                        <button type="button" class="btn btn-default" onclick="$('#modal_ajax').modal('hide');">
                            <?php echo get_phrase('cancel'); ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> <?php echo get_phrase('save'); ?>
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#house_select').on('change', function() {
        var house_id = $(this).val();
        $('#dormitory_select').html('<option value="">-- <?php echo get_phrase('loading'); ?> --</option>');
        
        if(house_id) {
            $.ajax({
                url: '<?php echo site_url('admin/get_dormitories_by_house/'); ?>' + house_id,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">-- <?php echo get_phrase('select'); ?> --</option>';
                    $.each(data, function(i, dorm) {
                        options += '<option value="' + dorm.dormitory_id + '">' + dorm.dormitory_name + '</option>';
                    });
                    $('#dormitory_select').html(options);
                }
            });
        }
    });
});
</script>
