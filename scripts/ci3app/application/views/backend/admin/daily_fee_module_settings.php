<style>
.module-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.module-card h3 {
    color: white;
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
}
.module-card p {
    color: rgba(255,255,255,0.9);
    margin: 0;
}
.settings-container {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.module-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-radius: 12px;
    background: #f8fafc;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.module-item:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.module-info {
    flex: 1;
}
.module-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.module-info p {
    margin: 0;
    color: #64748b;
    font-size: 1rem;
}
.module-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
}
.icon-feeding { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.icon-classes { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.icon-transport { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.icon-breakfast { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.icon-water { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
.toggle-switch {
    position: relative;
    width: 60px;
    height: 32px;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 32px;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 24px;
    width: 24px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
input:checked + .toggle-slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
input:checked + .toggle-slider:before {
    transform: translateX(28px);
}
.save-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 1.25rem 3.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.125rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
.save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}
</style>

<div class="row">
    <div class="col-md-12">
        <div class="module-card">
            <h3><i class="fa fa-cog"></i> Daily Fee Module Settings</h3>
            <p>Enable or disable fee collection modules for your school</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="settings-container">
            <?php echo form_open('admin/daily_fee_module_settings/update', ['id' => 'module_settings_form']); ?>
                
                <div class="module-item">
                    <div class="module-icon icon-feeding">
                        <i class="fa fa-utensils" style="color: white;"></i>
                    </div>
                    <div class="module-info">
                        <h4>
                            Feeding Fee
                            <?php if(is_fee_module_enabled('feeding')): ?>
                            <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem;">Active</span>
                            <?php endif; ?>
                        </h4>
                        <p>Collect daily feeding fees from students</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="fee_module_feeding" value="1" <?php echo is_fee_module_enabled('feeding') ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="module-item">
                    <div class="module-icon icon-classes">
                        <i class="fa fa-book" style="color: white;"></i>
                    </div>
                    <div class="module-info">
                        <h4>
                            Classes Fee
                            <?php if(is_fee_module_enabled('classes')): ?>
                            <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem;">Active</span>
                            <?php endif; ?>
                        </h4>
                        <p>Collect daily classes/tuition fees from students</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="fee_module_classes" value="1" <?php echo is_fee_module_enabled('classes') ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="module-item">
                    <div class="module-icon icon-transport">
                        <i class="fa fa-bus" style="color: white;"></i>
                    </div>
                    <div class="module-info">
                        <h4>
                            Transport Fare
                            <?php if(is_fee_module_enabled('transport')): ?>
                            <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem;">Active</span>
                            <?php endif; ?>
                        </h4>
                        <p>Collect transport fares from students using school buses</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="fee_module_transport" value="1" <?php echo is_fee_module_enabled('transport') ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="module-item">
                    <div class="module-icon icon-breakfast">
                        <i class="fa fa-coffee" style="color: white;"></i>
                    </div>
                    <div class="module-info">
                        <h4>
                            Breakfast Fee
                            <?php if(is_fee_module_enabled('breakfast')): ?>
                            <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem;">Active</span>
                            <?php endif; ?>
                        </h4>
                        <p>Collect breakfast fees from students</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="fee_module_breakfast" value="1" <?php echo is_fee_module_enabled('breakfast') ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="module-item">
                    <div class="module-icon icon-water">
                        <i class="fa fa-tint" style="color: white;"></i>
                    </div>
                    <div class="module-info">
                        <h4>
                            Water Fee
                            <?php if(is_fee_module_enabled('water')): ?>
                            <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem;">Active</span>
                            <?php endif; ?>
                        </h4>
                        <p>Collect water fees from students</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="fee_module_water" value="1" <?php echo is_fee_module_enabled('water') ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div style="margin-top: 2rem; text-align: right;">
                    <button type="submit" class="save-btn">
                        <i class="fa fa-save"></i> Save Settings
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
$('#module_settings_form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Saving...', 'loading');
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
            showAjaxModal_alert(response.message || 'Operation failed', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});
</script>
