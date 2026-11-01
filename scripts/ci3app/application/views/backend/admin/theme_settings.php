<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-palette"></i> <?php echo get_phrase('theme_customization'); ?>
                </div>
            </div>
            <div class="panel-body">
                
                <!-- Predefined Themes -->
                <h4 class="mb-3"><i class="fa fa-swatchbook"></i> <?php echo get_phrase('predefined_themes'); ?></h4>
                <div class="row theme-grid">
                    <?php
                    $current_theme = $this->db->get_where('settings', array('type' => 'app_theme'))->row()->description ?? 'default';
                    
                    $themes = array(
                        'default' => array('name' => 'Default Blue', 'primary' => '#667eea', 'secondary' => '#764ba2', 'accent' => '#f093fb'),
                        'ocean' => array('name' => 'Ocean Blue', 'primary' => '#2E3192', 'secondary' => '#1BFFFF', 'accent' => '#00d4ff'),
                        'sunset' => array('name' => 'Sunset Orange', 'primary' => '#f12711', 'secondary' => '#f5af19', 'accent' => '#ff6b6b'),
                        'forest' => array('name' => 'Forest Green', 'primary' => '#134E5E', 'secondary' => '#71B280', 'accent' => '#38ef7d'),
                        'purple' => array('name' => 'Royal Purple', 'primary' => '#5f27cd', 'secondary' => '#341f97', 'accent' => '#a29bfe'),
                        'crimson' => array('name' => 'Crimson Red', 'primary' => '#c0392b', 'secondary' => '#e74c3c', 'accent' => '#ff7979'),
                        'teal' => array('name' => 'Teal Mint', 'primary' => '#16a085', 'secondary' => '#1abc9c', 'accent' => '#48c9b0'),
                        'midnight' => array('name' => 'Midnight Blue', 'primary' => '#2c3e50', 'secondary' => '#34495e', 'accent' => '#3498db'),
                    );
                    
                    foreach($themes as $key => $theme):
                        $active = ($current_theme == $key) ? 'active' : '';
                    ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="theme-card <?php echo $active; ?>" onclick="applyTheme('<?php echo $key; ?>')">
                            <div class="theme-preview" style="background: linear-gradient(135deg, <?php echo $theme['primary']; ?> 0%, <?php echo $theme['secondary']; ?> 100%);">
                                <div class="theme-accent" style="background: <?php echo $theme['accent']; ?>;"></div>
                            </div>
                            <div class="theme-name">
                                <?php echo $theme['name']; ?>
                                <?php if($active): ?>
                                    <i class="fa fa-check-circle text-success"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <!-- Custom Theme -->
                <h4 class="mb-3"><i class="fa fa-paint-brush"></i> <?php echo get_phrase('custom_theme'); ?></h4>
                <?php echo form_open(site_url('admin/theme_settings/save_custom'), array('id' => 'custom_theme_form')); ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo get_phrase('primary_color'); ?></label>
                                <input type="color" class="form-control" name="primary_color" id="primary_color" 
                                    value="<?php echo $this->db->get_where('settings', array('type' => 'theme_primary'))->row()->description ?? '#667eea'; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo get_phrase('secondary_color'); ?></label>
                                <input type="color" class="form-control" name="secondary_color" id="secondary_color" 
                                    value="<?php echo $this->db->get_where('settings', array('type' => 'theme_secondary'))->row()->description ?? '#764ba2'; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo get_phrase('accent_color'); ?></label>
                                <input type="color" class="form-control" name="accent_color" id="accent_color" 
                                    value="<?php echo $this->db->get_where('settings', array('type' => 'theme_accent'))->row()->description ?? '#f093fb'; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo get_phrase('apply_custom_theme'); ?>
                            </button>
                            <button type="button" class="btn btn-default" onclick="previewCustomTheme()">
                                <i class="fa fa-eye"></i> <?php echo get_phrase('preview'); ?>
                            </button>
                        </div>
                    </div>
                <?php echo form_close(); ?>

            </div>
        </div>
    </div>
</div>

<style>
.theme-grid {
    margin-bottom: 20px;
}

.theme-card {
    border: 3px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
}

.theme-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.theme-card.active {
    border-color: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
}

.theme-preview {
    height: 120px;
    position: relative;
    display: flex;
    align-items: flex-end;
    padding: 10px;
}

.theme-accent {
    width: 100%;
    height: 30px;
    border-radius: 4px;
}

.theme-name {
    padding: 12px;
    text-align: center;
    font-weight: 600;
    background: #f8f9fa;
}

.mb-3 {
    margin-bottom: 1rem;
}

.my-4 {
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
}

input[type="color"] {
    height: 45px;
    cursor: pointer;
}
</style>

<script>
function applyTheme(themeName) {
    showAjaxModal_alert('<?php echo get_phrase('applying_theme'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/theme_settings/apply_theme'); ?>',
        type: 'POST',
        data: {theme: themeName},
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
}

$('#custom_theme_form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('<?php echo get_phrase('applying_custom_theme'); ?>...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});

function previewCustomTheme() {
    const primary = $('#primary_color').val();
    const secondary = $('#secondary_color').val();
    const accent = $('#accent_color').val();
    
    // Apply preview temporarily
    $('<style id="theme-preview">')
        .text(`
            .panel-primary > .panel-heading,
            .btn-primary,
            .modern-modal-header {
                background: linear-gradient(135deg, ${primary} 0%, ${secondary} 100%) !important;
            }
            .btn-primary:hover {
                background: ${accent} !important;
            }
        `)
        .appendTo('head');
    
    showAjaxModal_alert('<?php echo get_phrase('preview_applied'); ?>. <?php echo get_phrase('refresh_to_restore'); ?>', 'success', false);
}
</script>
