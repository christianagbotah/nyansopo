<?php
// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');

// Calculate grid columns based on enabled modules
$enabled_count = count(array_filter([$feeding_enabled, $breakfast_enabled, $classes_enabled, $water_enabled]));
$grid_columns = max($enabled_count, 1); // At least 1 column
?>
<style>
.rate-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
    border-left: 4px solid #667eea;
}
.rate-card.section-card {
    border-left: 4px solid #f59e0b;
    background: linear-gradient(to right, #fffbeb 0%, white 10%);
}
.rate-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}
.rate-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}
.rate-card-title {
    font-size: 18px;
    font-weight: 700;
    color: #333;
}
.rate-grid {
    display: grid;
    grid-template-columns: repeat(<?php echo $grid_columns; ?>, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}
.rate-item {
    text-align: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}
.rate-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}
.rate-value {
    font-size: 20px;
    font-weight: 700;
    color: #667eea;
}
.bulk-input {
    width: 100%;
    padding: 8px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    text-align: center;
}
.bulk-input:focus {
    border-color: #667eea;
    outline: none;
}
</style>

<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0; color: white; font-weight: 700;">
                        <i class="fa fa-money-bill-wave"></i> <?php echo get_phrase('daily_fee_rates_management'); ?>
                    </h4>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn" onclick="showBulkRateModal()" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; font-weight: 600; padding: 10px 24px; font-size: 15px; border: none; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">
                            <i class="fa fa-layer-group"></i> <?php echo get_phrase('bulk_assign_rates'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body" style="background: #f5f7fa; padding: 20px;" id="rates_container">
                <?php
                $running_year = get_settings('running_year');
                $running_term = get_settings('running_term');
                $classes = $this->db->get('class')->result_array();
                foreach ($classes as $class):
                    $sections = $this->db->get_where('section', ['class_id' => $class['class_id']])->result_array();
                    if (empty($sections)) {
                        $rate = $this->db->get_where('daily_fee_rates', [
                            'class_id' => $class['class_id'],
                            'year' => $running_year,
                            'term' => $running_term
                        ])->row();
                ?>
                <div class="rate-card">
                    <div class="rate-card-header">
                        <div class="rate-card-title">
                            <i class="fa fa-graduation-cap" style="color: #667eea;"></i> <?php echo $class['name'] . ' ' . $class['name_numeric']; ?>
                        </div>
                        <button class="btn btn-primary" onclick="showRateModal(<?php echo $class['class_id']; ?>, <?php echo $rate ? $rate->id : 0; ?>)">
                            <i class="fa fa-edit"></i> <?php echo $rate ? get_phrase('edit') : get_phrase('set_rates'); ?>
                        </button>
                    </div>
                    <div class="rate-grid">
                        <?php if($feeding_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-utensils"></i> <?php echo get_phrase('feeding'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->feeding_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if($breakfast_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-coffee"></i> <?php echo get_phrase('breakfast'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->breakfast_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if($classes_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-book"></i> <?php echo get_phrase('classes'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->classes_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if($water_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-tint"></i> <?php echo get_phrase('water'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->water_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                    } else {
                        foreach ($sections as $section):
                            $rate = $this->db->get_where('daily_fee_rates', [
                                'class_id' => $class['class_id'],
                                'year' => $running_year,
                                'term' => $running_term
                            ])->row();
                ?>
                <div class="rate-card section-card">
                    <div class="rate-card-header">
                        <div class="rate-card-title">
                            <i class="fa fa-graduation-cap" style="color: #f59e0b;"></i> <?php echo $class['name'] . ' ' . $class['name_numeric']; ?> <span style="color: #f59e0b; font-weight: 600;">- <?php echo $section['name']; ?></span>
                        </div>
                        <button class="btn btn-primary" onclick="showRateModal(<?php echo $class['class_id']; ?>, <?php echo $rate ? $rate->id : 0; ?>)">
                            <i class="fa fa-edit"></i> <?php echo $rate ? get_phrase('edit') : get_phrase('set_rates'); ?>
                        </button>
                    </div>
                    <div class="rate-grid">
                        <?php if($feeding_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-utensils"></i> <?php echo get_phrase('feeding'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->feeding_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if($breakfast_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-coffee"></i> <?php echo get_phrase('breakfast'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->breakfast_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if($classes_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-book"></i> <?php echo get_phrase('classes'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->classes_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if($water_enabled): ?>
                        <div class="rate-item">
                            <div class="rate-label"><i class="fa fa-tint"></i> <?php echo get_phrase('water'); ?></div>
                            <div class="rate-value">GHS <?php echo $rate ? number_format($rate->water_rate, 2) : '0.00'; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                        endforeach;
                    }
                endforeach;
                ?>
            </div>
        </div>
    </div>
</div>

<script>

function refreshRates() {
    $.ajax({
        url: '<?php echo site_url('admin/daily_fee_rates_content'); ?>',
        success: function(response) {
            $('#rates_container').html(response);
        }
    });
}
</script>
