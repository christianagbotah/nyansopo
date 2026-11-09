<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="entypo-users"></i> <?php echo get_phrase('assign_boarding_to_students'); ?></h3>
            </div>
            
            <div class="panel-body">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#single" data-toggle="tab"><i class="entypo-user"></i> <?php echo get_phrase('single_student'); ?></a></li>
                    <li><a href="#bulk" data-toggle="tab"><i class="entypo-users"></i> <?php echo get_phrase('bulk_assignment'); ?></a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="single">
                        <?php echo form_open(site_url('admin/assign_boarding/single'), array('class' => 'form-horizontal')); ?>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('select_student'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="student_id" class="form-control select2" required>
                                    <option value=""><?php echo get_phrase('select'); ?></option>
                                    <?php
                                    $students = $this->db->get_where('student', array('mute' => 0))->result_array();
                                    foreach($students as $student):
                                    ?>
                                    <option value="<?php echo $student['student_id']; ?>"><?php echo $student['name']; ?> (<?php echo $student['student_code']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('house'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="house_id" id="house_id_single" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select'); ?></option>
                                    <?php
                                    $houses = $this->db->get('house')->result_array();
                                    foreach($houses as $house):
                                    ?>
                                    <option value="<?php echo $house['house_id']; ?>"><?php echo $house['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('dormitory'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="dormitory_id" id="dormitory_id_single" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select_house_first'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('bed'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="bed_id" id="bed_id_single" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select_dormitory_first'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-7">
                                <button type="submit" class="btn btn-primary"><i class="entypo-check"></i> <?php echo get_phrase('assign'); ?></button>
                            </div>
                        </div>
                        </form>
                    </div>

                    <div class="tab-pane" id="bulk">
                        <?php echo form_open(site_url('admin/assign_boarding/bulk'), array('class' => 'form-horizontal')); ?>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('select_class'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="class_id" id="class_id_bulk" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select'); ?></option>
                                    <?php
                                    $classes = $this->db->get('class')->result_array();
                                    foreach($classes as $class):
                                    ?>
                                    <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name']; ?> <?php echo $class['name_numeric']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('select_students'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="student_ids[]" id="students_bulk" class="form-control select2" multiple required>
                                    <option value=""><?php echo get_phrase('select_class_first'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('house'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="house_id" id="house_id_bulk" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select'); ?></option>
                                    <?php foreach($houses as $house): ?>
                                    <option value="<?php echo $house['house_id']; ?>"><?php echo $house['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('dormitory'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-7">
                                <select name="dormitory_id" id="dormitory_id_bulk" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select_house_first'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-7">
                                <button type="submit" class="btn btn-primary"><i class="entypo-check"></i> <?php echo get_phrase('assign_bulk'); ?></button>
                                <p class="help-block"><?php echo get_phrase('beds_will_be_auto_assigned'); ?></p>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2();

    $('#house_id_single').change(function() {
        loadDormitories($(this).val(), 'single');
    });

    $('#dormitory_id_single').change(function() {
        loadBeds($(this).val(), 'single');
    });

    $('#house_id_bulk').change(function() {
        loadDormitories($(this).val(), 'bulk');
    });

    $('#class_id_bulk').change(function() {
        loadStudentsByClass($(this).val());
    });
});

function loadDormitories(house_id, type) {
    $.ajax({
        url: '<?php echo site_url('admin/get_dormitories_by_house'); ?>',
        type: 'POST',
        data: {house_id: house_id},
        success: function(response) {
            $('#dormitory_id_' + type).html(response);
        }
    });
}

function loadBeds(dormitory_id, type) {
    $.ajax({
        url: '<?php echo site_url('admin/get_beds_by_dormitory'); ?>',
        type: 'POST',
        data: {dormitory_id: dormitory_id},
        success: function(response) {
            $('#bed_id_' + type).html(response);
        }
    });
}

function loadStudentsByClass(class_id) {
    $.ajax({
        url: '<?php echo site_url('admin/get_students_by_class_json'); ?>',
        type: 'POST',
        data: {class_id: class_id},
        success: function(response) {
            $('#students_bulk').html(response);
        }
    });
}
</script>
