<?php
$hubtel_settings = [
    'client_id' => get_settings('hubtel_client_id'),
    'client_secret' => get_settings('hubtel_client_secret'),
    'merchant_number' => get_settings('hubtel_merchant_number'),
    'test_mode' => get_settings('hubtel_test_mode'),
    'enabled' => get_settings('hubtel_enabled')
];
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="mb-0 font-size-18"><?php echo get_phrase('gateway_settings'); ?></h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">
                        <img src="<?php echo base_url('assets/images/gateways/hubtel.png'); ?>" 
                             alt="Hubtel" style="height: 40px; margin-right: 10px;"
                             onerror="this.style.display='none'">
                        <?php echo get_phrase('hubtel_payment_gateway'); ?>
                    </h4>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="hubtel-enabled" 
                               <?php echo $hubtel_settings['enabled'] == '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="hubtel-enabled">
                            <?php echo get_phrase('enable_gateway'); ?>
                        </label>
                    </div>
                </div>

                <form id="hubtelSettingsForm" action="<?php echo site_url('finance/save_gateway_settings/hubtel'); ?>" method="post">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i>
                        <strong><?php echo get_phrase('note'); ?>:</strong> 
                        <?php echo get_phrase('hubtel_supports_all_networks'); ?>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('client_id'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="client_id" class="form-control" 
                               value="<?php echo $hubtel_settings['client_id']; ?>" 
                               placeholder="e.g., abcdefgh" required>
                        <small class="form-text text-muted">
                            <?php echo get_phrase('get_from_hubtel_dashboard'); ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('client_secret'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="client_secret" id="client-secret" class="form-control" 
                                   value="<?php echo $hubtel_settings['client_secret']; ?>" 
                                   placeholder="••••••••••••" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleSecret()">
                                    <i class="mdi mdi-eye" id="secret-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('merchant_account_number'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="merchant_number" class="form-control" 
                               value="<?php echo $hubtel_settings['merchant_number']; ?>" 
                               placeholder="e.g., HM0000000" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('mode'); ?></label>
                        <select name="test_mode" class="form-control">
                            <option value="1" <?php echo $hubtel_settings['test_mode'] == '1' ? 'selected' : ''; ?>>
                                <?php echo get_phrase('test_mode'); ?> (Sandbox)
                            </option>
                            <option value="0" <?php echo $hubtel_settings['test_mode'] == '0' ? 'selected' : ''; ?>>
                                <?php echo get_phrase('live_mode'); ?> (Production)
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo get_phrase('use_test_mode_for_testing'); ?>
                        </small>
                    </div>

                    <hr>

                    <h5 class="mb-3"><?php echo get_phrase('webhook_configuration'); ?></h5>

                    <div class="form-group">
                        <label><?php echo get_phrase('callback_url'); ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" 
                                   value="<?php echo site_url('payment_gateway/hubtel_callback'); ?>" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">
                                    <i class="mdi mdi-content-copy"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <?php echo get_phrase('add_this_url_to_hubtel_dashboard'); ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('webhook_url'); ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" 
                                   value="<?php echo site_url('payment_gateway/webhook_hubtel'); ?>" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">
                                    <i class="mdi mdi-content-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="mdi mdi-content-save"></i> <?php echo get_phrase('save_settings'); ?>
                        </button>
                        <button type="button" class="btn btn-info btn-lg" onclick="testConnection()">
                            <i class="mdi mdi-test-tube"></i> <?php echo get_phrase('test_connection'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="text-white"><i class="mdi mdi-information-outline"></i> <?php echo get_phrase('setup_guide'); ?></h5>
                <hr class="bg-white">
                
                <h6 class="text-white"><?php echo get_phrase('step'); ?> 1</h6>
                <p class="small"><?php echo get_phrase('create_hubtel_account'); ?></p>
                
                <h6 class="text-white mt-3"><?php echo get_phrase('step'); ?> 2</h6>
                <p class="small"><?php echo get_phrase('get_api_credentials'); ?></p>
                
                <h6 class="text-white mt-3"><?php echo get_phrase('step'); ?> 3</h6>
                <p class="small"><?php echo get_phrase('configure_credentials_here'); ?></p>
                
                <h6 class="text-white mt-3"><?php echo get_phrase('step'); ?> 4</h6>
                <p class="small"><?php echo get_phrase('test_with_sandbox'); ?></p>
                
                <h6 class="text-white mt-3"><?php echo get_phrase('step'); ?> 5</h6>
                <p class="small"><?php echo get_phrase('go_live'); ?></p>
                
                <a href="https://developers.hubtel.com" target="_blank" class="btn btn-light btn-sm mt-3">
                    <i class="mdi mdi-book-open"></i> <?php echo get_phrase('view_documentation'); ?>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5><i class="mdi mdi-network"></i> <?php echo get_phrase('supported_networks'); ?></h5>
                <hr>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="mdi mdi-check text-success"></i> MTN Mobile Money</li>
                    <li class="mb-2"><i class="mdi mdi-check text-success"></i> Vodafone Cash</li>
                    <li class="mb-2"><i class="mdi mdi-check text-success"></i> AirtelTigo Money</li>
                    <li class="mb-2"><i class="mdi mdi-check text-success"></i> Tigo Cash</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#hubtel-enabled').change(function() {
        const enabled = $(this).is(':checked') ? 1 : 0;
        $.post('<?php echo site_url('finance/toggle_gateway/hubtel'); ?>', {enabled: enabled}, function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            showAjaxModal_alert(data.message, data.status);
        });
    });
});

$('#hubtelSettingsForm').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false
    }).done(function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if(data.status === 'success') {
            showAjaxModal_alert(data.message, 'success');
        } else {
            showAjaxModal_alert(data.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});

function toggleSecret() {
    const input = document.getElementById('client-secret');
    const icon = document.getElementById('secret-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('mdi-eye');
        icon.classList.add('mdi-eye-off');
    } else {
        input.type = 'password';
        icon.classList.remove('mdi-eye-off');
        icon.classList.add('mdi-eye');
    }
}

function copyToClipboard(btn) {
    const input = btn.closest('.input-group').querySelector('input');
    input.select();
    document.execCommand('copy');
    
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="mdi mdi-check"></i>';
    setTimeout(() => {
        btn.innerHTML = originalHtml;
    }, 2000);
}

function testConnection() {
    showAjaxModal_alert('<?php echo get_phrase('testing_connection'); ?>...', 'loading');
    
    $.get('<?php echo site_url('finance/test_hubtel_connection'); ?>', function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        showAjaxModal_alert(data.message, data.status);
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('connection_test_failed'); ?>', 'error');
    });
}
</script>
