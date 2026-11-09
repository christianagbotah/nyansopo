<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-plus-circle"></i> <?php echo get_phrase('bulk_add_beds'); ?>
                </h3>
            </div>
            <div class="panel-body">
                
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong><?php echo get_phrase('how_it_works'); ?>:</strong><br>
                    <?php echo get_phrase('bulk_add_beds_help'); ?>
                    <br><br>
                    <strong><?php echo get_phrase('example'); ?>:</strong><br>
                    Prefix: "A-", Start: 1, End: 50<br>
                    Will create: A-001, A-002, A-003, ..., A-050
                </div>
                
                <form action="<?php echo site_url('admin/manageDormitoryBed/bulk_create'); ?>" method="post" id="bulk_bed_form">
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('select_house'); ?> *</label>
                        <select id="bulk_house_select" class="form-control" required>
                            <option value="">-- <?php echo get_phrase('select'); ?> --</option>
                            <?php
                            $houses = $this->boarding_model->getAllHouses();
                            foreach($houses as $house):
                            ?>
                            <option value="<?php echo $house['house_id']; ?>"><?php echo $house['house_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('select_dormitory'); ?> *</label>
                        <select name="dormitory_id" id="bulk_dormitory_select" class="form-control" required>
                            <option value="">-- <?php echo get_phrase('select_house_first'); ?> --</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo get_phrase('bed_code_prefix'); ?> *</label>
                                <input type="text" name="bed_code_prefix" id="bed_prefix" class="form-control" 
                                       placeholder="e.g., A-" required maxlength="10">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo get_phrase('start_number'); ?> *</label>
                                <input type="number" name="start_number" id="start_num" class="form-control" 
                                       placeholder="1" required min="1" value="1">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo get_phrase('end_number'); ?> *</label>
                                <input type="number" name="end_number" id="end_num" class="form-control" 
                                       placeholder="50" required min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning" id="preview_alert" style="display: none;">
                        <strong><?php echo get_phrase('preview'); ?>:</strong>
                        <div id="preview_text"></div>
                        <div id="total_beds" style="margin-top: 10px;"></div>
                    </div>
                    
                    <div class="form-group text-right">
                        <button type="button" class="btn btn-default" onclick="$('#modal_ajax').modal('hide');">
                            <?php echo get_phrase('cancel'); ?>
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit_btn" disabled>
                            <i class="fa fa-save"></i> <?php echo get_phrase('create_beds'); ?>
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load dormitories when house is selected
    $('#bulk_house_select').on('change', function() {
        var house_id = $(this).val();
        $('#bulk_dormitory_select').html('<option value="">-- <?php echo get_phrase('loading'); ?> --</option>');
        
        if(house_id) {
            $.ajax({
                url: '<?php echo site_url('admin/get_dormitories_by_house/'); ?>' + house_id,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">-- <?php echo get_phrase('select'); ?> --</option>';
                    $.each(data, function(i, dorm) {
                        options += '<option value="' + dorm.dormitory_id + '" data-prefix="' + dorm.bed_code_prefix + '">' + dorm.dormitory_name + '</option>';
                    });
                    $('#bulk_dormitory_select').html(options);
                }
            });
        }
    });
    
    // Auto-fill prefix when dormitory is selected
    $('#bulk_dormitory_select').on('change', function() {
        var prefix = $(this).find(':selected').data('prefix');
        if(prefix) {
            $('#bed_prefix').val(prefix);
        }
        updatePreview();
    });
    
    // Update preview when inputs change
    $('#bed_prefix, #start_num, #end_num').on('input', function() {
        updatePreview();
    });
    
    function updatePreview() {
        var prefix = $('#bed_prefix').val();
        var start = parseInt($('#start_num').val());
        var end = parseInt($('#end_num').val());
        
        if(prefix && start && end && start <= end) {
            var total = end - start + 1;
            
            if(total > 100) {
                $('#preview_alert').removeClass('alert-warning').addClass('alert-danger');
                $('#preview_text').html('<?php echo get_phrase('warning'); ?>: Creating ' + total + ' beds. This may take a while.');
            } else {
                $('#preview_alert').removeClass('alert-danger').addClass('alert-warning');
            }
            
            var preview = '';
            var samples = Math.min(5, total);
            for(var i = 0; i < samples; i++) {
                var num = start + i;
                var code = prefix + String(num).padStart(3, '0');
                preview += '<span class="label label-info" style="margin: 2px;">' + code + '</span> ';
            }
            if(total > samples) {
                preview += '<span>... and ' + (total - samples) + ' more</span>';
            }
            
            $('#preview_text').html(preview);
            $('#total_beds').html('<strong><?php echo get_phrase('total_beds_to_create'); ?>: ' + total + '</strong>');
            $('#preview_alert').show();
            $('#submit_btn').prop('disabled', false);
        } else {
            $('#preview_alert').hide();
            $('#submit_btn').prop('disabled', true);
        }
    }
    
    // Form validation
    $('#bulk_bed_form').on('submit', function(e) {
        var start = parseInt($('#start_num').val());
        var end = parseInt($('#end_num').val());
        
        if(start > end) {
            e.preventDefault();
            alert('<?php echo get_phrase('start_number_must_be_less_than_end_number'); ?>');
            return false;
        }
        
        var total = end - start + 1;
        if(total > 200) {
            if(!confirm('<?php echo get_phrase('you_are_about_to_create'); ?> ' + total + ' <?php echo get_phrase('beds'); ?>. <?php echo get_phrase('continue'); ?>?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
