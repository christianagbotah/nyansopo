<?php
$house_id = $this->uri->segment(4);
$edit_mode = !empty($house_id);
$house = $edit_mode ? $this->boarding_model->getHouseById($house_id) : [];
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-home"></i> 
                    <?php echo $edit_mode ? get_phrase('edit_boarding_house') : get_phrase('add_boarding_house'); ?>
                </h3>
            </div>
            <div class="panel-body">
                
                <form action="<?php echo site_url('admin/manageBoardingHouse/' . ($edit_mode ? 'update/' . $house_id : 'create')); ?>" 
                      method="post" enctype="multipart/form-data">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('house_name'); ?> *</label>
                                <input type="text" name="house_name" class="form-control" 
                                       value="<?php echo $edit_mode ? $house['house_name'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('capacity'); ?> (<?php echo get_phrase('number_of_dormitories'); ?>) *</label>
                                <input type="number" name="house_capacity" class="form-control" 
                                       value="<?php echo $edit_mode ? $house['house_capacity'] : ''; ?>" required min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('house_fee'); ?> (<?php echo get_settings('currency'); ?>)</label>
                                <input type="number" step="0.01" name="house_user_fee" class="form-control" 
                                       value="<?php echo $edit_mode ? $house['house_user_fee'] : '0.00'; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('year_established'); ?></label>
                                <input type="number" name="house_year_established" class="form-control" 
                                       value="<?php echo $edit_mode ? $house['house_year_established'] : date('Y'); ?>" 
                                       min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('house_master'); ?></label>
                                <select name="house_master_id" class="form-control select2">
                                    <option value="">-- <?php echo get_phrase('select_teacher'); ?> --</option>
                                    <?php
                                    $teachers = $this->db->get('teacher')->result_array();
                                    foreach($teachers as $teacher):
                                    ?>
                                    <option value="<?php echo $teacher['teacher_id']; ?>" 
                                            <?php echo ($edit_mode && $house['house_master_id'] == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                                        <?php echo $teacher['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('house_prefect'); ?></label>
                                <select name="house_prefect_id" class="form-control select2">
                                    <option value="">-- <?php echo get_phrase('select_student'); ?> --</option>
                                    <?php
                                    $students = $this->db->get('student')->result_array();
                                    foreach($students as $student):
                                    ?>
                                    <option value="<?php echo $student['student_id']; ?>" 
                                            <?php echo ($edit_mode && $house['house_prefect_id'] == $student['student_id']) ? 'selected' : ''; ?>>
                                        <?php echo $student['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('gps_code'); ?></label>
                                <input type="text" name="house_gps_code" class="form-control" 
                                       value="<?php echo $edit_mode ? $house['house_gps_code'] : ''; ?>" 
                                       placeholder="e.g., GH-123-4567">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('house_image'); ?></label>
                                <input type="file" name="house_image" class="form-control" accept="image/*">
                                <?php if($edit_mode && !empty($house['house_image'])): ?>
                                    <small class="text-muted"><?php echo get_phrase('current_image'); ?>: <?php echo $house['house_image']; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?></label>
                        <textarea name="house_description" class="form-control" rows="3"><?php echo $edit_mode ? $house['house_description'] : ''; ?></textarea>
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
