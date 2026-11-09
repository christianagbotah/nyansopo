<?php
$dormitory_id = $this->uri->segment(4);
$edit_mode = !empty($dormitory_id);
$dormitory = $edit_mode ? $this->boarding_model->getDormitoryById($dormitory_id) : [];
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-building-o"></i> 
                    <?php echo $edit_mode ? get_phrase('edit_dormitory') : get_phrase('add_dormitory'); ?>
                </h3>
            </div>
            <div class="panel-body">
                
                <form action="<?php echo site_url('admin/manageBoardingDormitory/' . ($edit_mode ? 'update/' . $dormitory_id : 'create')); ?>" 
                      method="post">
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('dormitory_name'); ?> *</label>
                        <input type="text" name="dormitory_name" class="form-control" 
                               value="<?php echo $edit_mode ? $dormitory['dormitory_name'] : ''; ?>" 
                               placeholder="e.g., Dormitory A" required>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('select_house'); ?> *</label>
                        <select name="house_id" class="form-control" required>
                            <option value="">-- <?php echo get_phrase('select'); ?> --</option>
                            <?php
                            $houses = $this->boarding_model->getAllHouses();
                            foreach($houses as $house):
                            ?>
                            <option value="<?php echo $house['house_id']; ?>" 
                                    <?php echo ($edit_mode && $dormitory['house_id'] == $house['house_id']) ? 'selected' : ''; ?>>
                                <?php echo $house['house_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('bed_capacity'); ?> *</label>
                        <input type="number" name="bed_capacity" class="form-control" 
                               value="<?php echo $edit_mode ? $dormitory['bed_capacity'] : ''; ?>" 
                               placeholder="e.g., 50" required min="1">
                        <small class="text-muted"><?php echo get_phrase('maximum_number_of_beds'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('bed_code_prefix'); ?></label>
                        <input type="text" name="bed_code_prefix" class="form-control" 
                               value="<?php echo $edit_mode ? $dormitory['bed_code_prefix'] : ''; ?>" 
                               placeholder="e.g., A-" maxlength="10">
                        <small class="text-muted"><?php echo get_phrase('prefix_for_bed_codes'); ?> (e.g., A-001, A-002)</small>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('dormitory_prefect'); ?></label>
                        <select name="dormitory_prefect_id" class="form-control select2">
                            <option value="">-- <?php echo get_phrase('select_student'); ?> --</option>
                            <?php
                            $students = $this->db->get('student')->result_array();
                            foreach($students as $student):
                            ?>
                            <option value="<?php echo $student['student_id']; ?>" 
                                    <?php echo ($edit_mode && $dormitory['dormitory_prefect_id'] == $student['student_id']) ? 'selected' : ''; ?>>
                                <?php echo $student['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?></label>
                        <textarea name="dormitory_description" class="form-control" rows="3"><?php echo $edit_mode ? $dormitory['dormitory_description'] : ''; ?></textarea>
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
    $('.select2').select2({
        dropdownParent: $('#modal_ajax')
    });
});
</script>
