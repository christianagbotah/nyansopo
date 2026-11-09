<style>
.import-step {
    display: none;
}
.import-step.active {
    display: block;
}
.rate-preview-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
.rate-preview-table th {
    background: #f8f9fa;
    padding: 15px;
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
}
.rate-preview-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
}
.rate-preview-table input {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
}
.rate-preview-table input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
</style>

<div id="import_step_1" class="import-step active">
    <div class="alert alert-info" style="border-left: 4px solid #10b981; background: #ecfdf5; color: #065f46;">
        <i class="fa fa-info-circle"></i>
        <strong><?php echo get_phrase('select_source_term'); ?>:</strong> 
        <?php echo get_phrase('choose_the_term_to_import_rates_from'); ?>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 15px;"><?php echo get_phrase('year'); ?>:</label>
                <select id="import_year" class="form-control" style="height: 45px; font-size: 16px;">
                    <?php
                    $current_year = get_settings('running_year');
                    $years = explode('-', $current_year);
                    $start_year = intval($years[0]);
                    
                    // Show last 3 years
                    for ($i = 0; $i < 3; $i++) {
                        $year_val = ($start_year - $i) . '-' . ($start_year - $i + 1);
                        echo '<option value="' . $year_val . '">' . $year_val . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 15px;"><?php echo get_phrase('term'); ?>:</label>
                <select id="import_term" class="form-control" style="height: 45px; font-size: 16px;">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </div>
        </div>
    </div>
    
    <button type="button" class="btn btn-lg" onclick="loadPreviousRates()" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
        <i class="fa fa-download"></i> <?php echo get_phrase('load_rates'); ?>
    </button>
</div>

<div id="import_step_2" class="import-step">
    <div class="alert alert-success" style="border-left: 4px solid #10b981; background: #ecfdf5; color: #065f46;">
        <i class="fa fa-check-circle"></i>
        <strong><?php echo get_phrase('review_and_edit'); ?>:</strong> 
        <?php echo get_phrase('review_rates_below_edit_if_needed_then_import'); ?>
    </div>
    
    <div id="preview_rates_container" style="max-height: 400px; overflow-y: auto;"></div>
    
    <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" class="btn btn-default" onclick="backToStep1()" style="padding: 10px 20px; font-size: 15px;">
            <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back'); ?>
        </button>
        <button type="button" class="btn btn-success btn-lg" onclick="confirmImport()" style="padding: 12px 30px; font-size: 16px; font-weight: 600;">
            <i class="fa fa-check"></i> <?php echo get_phrase('confirm_import'); ?>
        </button>
    </div>
</div>

<script>
function loadPreviousRates() {
    var year = $('#import_year').val();
    var term = $('#import_term').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/get_previous_term_rates'); ?>',
        type: 'POST',
        data: { year: year, term: term },
        dataType: 'json',
        beforeSend: function() {
            showAjaxModal_alert('<?php echo get_phrase('loading_rates'); ?>...', 'loading');
        },
        success: function(response) {
            closeAjaxModal();
            
            if (response.status === 'success') {
                if (response.rates.length === 0) {
                    showAjaxModal_alert('<?php echo get_phrase('no_rates_found_for_selected_term'); ?>', 'error');
                } else {
                    $('#preview_rates_container').html(response.html);
                    $('#import_step_1').removeClass('active');
                    $('#import_step_2').addClass('active');
                }
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_loading_rates'); ?>', 'error');
        }
    });
}

function backToStep1() {
    $('#import_step_2').removeClass('active');
    $('#import_step_1').addClass('active');
}

function confirmImport() {
    // Gather all edited rates
    var rates = [];
    $('.import-rate-row').each(function() {
        var classId = $(this).data('class-id');
        rates.push({
            class_id: classId,
            feeding_rate: $(this).find('.feeding-rate').val(),
            breakfast_rate: $(this).find('.breakfast-rate').val(),
            classes_rate: $(this).find('.classes-rate').val(),
            water_rate: $(this).find('.water-rate').val()
        });
    });
    
    if (rates.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase('no_rates_to_import'); ?>', 'error');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url('admin/import_daily_fee_rates'); ?>',
        type: 'POST',
        data: { rates: JSON.stringify(rates) },
        dataType: 'json',
        beforeSend: function() {
            showAjaxModal_alert('<?php echo get_phrase('importing_rates'); ?>...', 'loading');
        },
        success: function(response) {
            if (response.status === 'success') {
                showAjaxModal_alert(response.message, 'success', false);
                $('#modal_ajax').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_importing_rates'); ?>', 'error');
        }
    });
}
</script>
