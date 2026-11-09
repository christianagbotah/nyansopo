<!-- Manage User Permissions -->
<div class="row">
    <div class="col-md-12">
        
        <!-- Page Header -->
        <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:32px;border-radius:16px;margin-bottom:24px;box-shadow:0 10px 40px rgba(102,126,234,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                <div style="flex:1;">
                    <h2 style="color:#fff;margin:0 0 8px 0;font-size:28px;font-weight:700;display:flex;align-items:center;gap:12px;">
                        <i class="entypo-lock"></i>
                        <?php echo get_phrase('manage_permissions_for'); ?>: <?php echo htmlspecialchars($user_info['name']); ?>
                    </h2>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <span style="color:rgba(255,255,255,0.9);font-size:14px;">
                            <i class="entypo-mail"></i> <?php echo htmlspecialchars($user_info['email']); ?>
                        </span>
                        <span style="display:inline-block;background:rgba(255,255,255,0.2);color:#fff;padding:4px 12px;border-radius:12px;font-size:13px;font-weight:600;">
                            <?php echo ucfirst($user_type); ?>
                        </span>
                    </div>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="<?php echo site_url('user_permissions/list_users/' . $user_type); ?>" class="modern-btn modern-btn-light" style="background:rgba(255,255,255,0.2);color:#fff;text-decoration:none;">
                        <i class="entypo-left-open-big"></i> <?php echo get_phrase('back_to_list'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Permission Matrix -->
        <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:24px;">
            <div style="padding:24px;border-bottom:2px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">
                <h4 style="margin:0;font-size:18px;font-weight:600;color:#1f2937;">
                    <i class="entypo-grid"></i> <?php echo get_phrase('permissions_matrix'); ?>
                </h4>
                <div id="save-status" style="display:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;"></div>
            </div>
            
            <div class="table-responsive">
                <table class="table" style="margin:0;">
                    <thead style="background:#f9fafb;">
                        <tr>
                            <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;min-width:250px;"><?php echo get_phrase('module'); ?></th>
                            <?php 
                                // Get all possible permissions
                                $all_perms = [];
                                foreach ($modules as $module) {
                                    $perms = explode(',', $module['available_permissions']);
                                    foreach ($perms as $perm) {
                                        $perm = trim($perm);
                                        if (!in_array($perm, $all_perms)) {
                                            $all_perms[] = $perm;
                                        }
                                    }
                                }
                                foreach ($all_perms as $perm):
                            ?>
                                <th class="text-center" style="padding:16px;font-weight:600;color:#374151;font-size:14px;">
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                                        <i class="<?php echo get_permission_icon($perm); ?>" style="font-size:18px;"></i>
                                        <span><?php echo ucfirst($perm); ?></span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center" style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('quick_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permission_matrix as $module_name => $data): ?>
                            <?php $module = $data['module']; ?>
                            <?php $perms = $data['permissions']; ?>
                            <tr style="transition:background 0.2s;" data-module="<?php echo $module_name; ?>">
                                <td style="padding:16px;vertical-align:middle;">
                                    <div>
                                        <div style="font-weight:600;color:#1f2937;font-size:14px;margin-bottom:4px;">
                                            <?php echo htmlspecialchars($module['module_display_name']); ?>
                                        </div>
                                        <div style="font-size:12px;color:#6b7280;">
                                            <?php echo htmlspecialchars($module['module_description']); ?>
                                        </div>
                                    </div>
                                </td>
                                <?php foreach ($all_perms as $perm): ?>
                                    <td class="text-center" style="padding:16px;vertical-align:middle;">
                                        <?php 
                                            $available_perms = explode(',', $module['available_permissions']);
                                            $available_perms = array_map('trim', $available_perms);
                                            $is_available = in_array($perm, $available_perms);
                                            $has_permission = isset($perms[$perm]) && $perms[$perm];
                                        ?>
                                        <?php if ($is_available): ?>
                                            <label class="checkbox-wrapper" style="display:inline-block;cursor:pointer;">
                                                <input type="checkbox" 
                                                       class="permission-checkbox"
                                                       data-module="<?php echo $module_name; ?>"
                                                       data-permission="<?php echo $perm; ?>"
                                                       <?php echo $has_permission ? 'checked' : ''; ?>
                                                       style="width:20px;height:20px;cursor:pointer;">
                                            </label>
                                        <?php else: ?>
                                            <span style="color:#d1d5db;">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center" style="padding:16px;vertical-align:middle;">
                                    <button class="grant-all-btn" 
                                            data-module="<?php echo $module_name; ?>"
                                            title="<?php echo get_phrase('grant_all'); ?>"
                                            style="padding:8px 12px;border:none;border-radius:6px;background:#d1fae5;color:#065f46;cursor:pointer;transition:all 0.2s;font-size:12px;font-weight:600;margin-right:4px;">
                                        <i class="entypo-check"></i> <?php echo get_phrase('all'); ?>
                                    </button>
                                    <button class="revoke-all-btn" 
                                            data-module="<?php echo $module_name; ?>"
                                            title="<?php echo get_phrase('revoke_all'); ?>"
                                            style="padding:8px 12px;border:none;border-radius:6px;background:#fee2e2;color:#991b1b;cursor:pointer;transition:all 0.2s;font-size:12px;font-weight:600;">
                                        <i class="entypo-cancel"></i> <?php echo get_phrase('none'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Current Permissions Summary -->
        <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;">
            <div style="padding:24px;border-bottom:2px solid #f3f4f6;">
                <h4 style="margin:0;font-size:18px;font-weight:600;color:#1f2937;">
                    <i class="entypo-list"></i> <?php echo get_phrase('current_permissions_summary'); ?>
                </h4>
            </div>
            
            <div style="padding:24px;">
                <div id="permissions-summary" class="row">
                    <?php if (!empty($user_permissions)): ?>
                        <?php 
                            $grouped = [];
                            foreach ($user_permissions as $perm) {
                                $grouped[$perm['module_name']][] = $perm;
                            }
                        ?>
                        <?php foreach ($grouped as $module_name => $module_perms): ?>
                            <div class="col-md-4 col-sm-6" style="margin-bottom:16px;">
                                <div style="padding:16px;border:2px solid #e5e7eb;border-radius:8px;">
                                    <h5 style="margin:0 0 12px 0;font-size:14px;font-weight:600;color:#1f2937;">
                                        <?php echo htmlspecialchars($module_perms[0]['module_display_name']); ?>
                                    </h5>
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                        <?php foreach ($module_perms as $perm): ?>
                                            <?php echo format_permission_badge($perm['permission_type']); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-md-12">
                            <div style="text-align:center;padding:32px;color:#9ca3af;">
                                <i class="entypo-info" style="font-size:48px;display:block;margin-bottom:16px;"></i>
                                <p style="font-size:16px;margin:0;font-weight:500;"><?php echo get_phrase('no_permissions_granted_yet'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
$(document).ready(function() {
    var userId = <?php echo $user_id; ?>;
    var userType = '<?php echo $user_type; ?>';
    
    // Handle individual permission checkbox changes
    $('.permission-checkbox').on('change', function() {
        var $checkbox = $(this);
        var module = $checkbox.data('module');
        var permission = $checkbox.data('permission');
        var isChecked = $checkbox.is(':checked');
        
        if (isChecked) {
            grantPermission(userId, userType, module, permission, $checkbox);
        } else {
            revokePermission(userId, userType, module, permission, $checkbox);
        }
    });
    
    // Grant all permissions for a module
    $('.grant-all-btn').on('click', function() {
        var $btn = $(this);
        var module = $btn.data('module');
        var moduleName = $('tr[data-module="' + module + '"]').find('td:first').text().trim().split('\n')[0].trim();
        
        // Use custom confirm modal
        showConfirmModal(
            '<?php echo get_phrase('grant_all_permissions'); ?>',
            '<?php echo get_phrase('grant_all_available_permissions_for'); ?> "<strong>' + moduleName + '</strong>"?',
            function() {
                // User confirmed - proceed with grant
                $btn.prop('disabled', true);
                showStatus('<?php echo get_phrase('granting_all_permissions'); ?>...', 'info');
                
                $.ajax({
                    url: '<?php echo site_url('user_permissions/grant_all_module_permissions'); ?>',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        user_type: userType,
                        module_name: module
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showStatus(response.message, 'success');
                            // Check all checkboxes for this module
                            $('tr[data-module="' + module + '"]').find('.permission-checkbox').prop('checked', true);
                            
                            // Show success alert modal
                            showAjaxModal_alert(response.message || '<?php echo get_phrase('all_permissions_granted_successfully'); ?>', 'success', true, true);
                        } else {
                            showStatus(response.message, 'error');
                            $btn.prop('disabled', false);
                            showAjaxModal_alert(response.message || '<?php echo get_phrase('error_granting_permissions'); ?>', 'error', false, true);
                        }
                    },
                    error: function() {
                        showStatus('<?php echo get_phrase('error_granting_permissions'); ?>', 'error');
                        $btn.prop('disabled', false);
                        showAjaxModal_alert('<?php echo get_phrase('error_granting_permissions'); ?>', 'error', false, true);
                    }
                });
            },
            '<?php echo get_phrase('grant_all'); ?>',
            'info'
        );
    });
    
    // Revoke all permissions for a module
    $('.revoke-all-btn').on('click', function() {
        var $btn = $(this);
        var module = $btn.data('module');
        var moduleName = $('tr[data-module="' + module + '"]').find('td:first').text().trim().split('\n')[0].trim();
        
        // Use custom confirm modal
        showConfirmModal(
            '<?php echo get_phrase('revoke_all_permissions'); ?>',
            '<?php echo get_phrase('are_you_sure_you_want_to_revoke_all_permissions_for'); ?> "<strong>' + moduleName + '</strong>"?<br><br><span style="color:#e74c3c;"><?php echo get_phrase('user_will_lose_all_access_to_this_module'); ?></span>',
            function() {
                // User confirmed - proceed with revoke
                $btn.prop('disabled', true);
                showStatus('<?php echo get_phrase('revoking_all_permissions'); ?>...', 'info');
                
                $.ajax({
                    url: '<?php echo site_url('user_permissions/revoke_all_module_permissions'); ?>',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        user_type: userType,
                        module_name: module
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showStatus(response.message, 'success');
                            // Uncheck all checkboxes for this module
                            $('tr[data-module="' + module + '"]').find('.permission-checkbox').prop('checked', false);
                            
                            // Show success alert modal
                            showAjaxModal_alert(response.message || '<?php echo get_phrase('all_permissions_revoked_successfully'); ?>', 'success', true, true);
                        } else {
                            showStatus(response.message, 'error');
                            $btn.prop('disabled', false);
                            showAjaxModal_alert(response.message || '<?php echo get_phrase('error_revoking_permissions'); ?>', 'error', false, true);
                        }
                    },
                    error: function() {
                        showStatus('<?php echo get_phrase('error_revoking_permissions'); ?>', 'error');
                        $btn.prop('disabled', false);
                        showAjaxModal_alert('<?php echo get_phrase('error_revoking_permissions'); ?>', 'error', false, true);
                    }
                });
            },
            '<?php echo get_phrase('revoke_all'); ?>',
            'danger'
        );
    });
    
    function grantPermission(userId, userType, module, permission, $checkbox) {
        showStatus('<?php echo get_phrase('granting_permission'); ?>...', 'info');
        
        $.ajax({
            url: '<?php echo site_url('user_permissions/grant_permission'); ?>',
            method: 'POST',
            data: {
                user_id: userId,
                user_type: userType,
                module_name: module,
                permission_type: permission
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showStatus('<?php echo get_phrase('permission_granted_successfully'); ?>', 'success');
                    // Use toastr for non-intrusive notification
                    toastr.success(response.message || '<?php echo get_phrase('permission_granted_successfully'); ?>');
                    setTimeout(function() {
                        $('#save-status').fadeOut();
                    }, 2000);
                } else {
                    showStatus(response.message, 'error');
                    $checkbox.prop('checked', false);
                    toastr.error(response.message || '<?php echo get_phrase('error_granting_permission'); ?>');
                }
            },
            error: function() {
                showStatus('<?php echo get_phrase('error_granting_permission'); ?>', 'error');
                $checkbox.prop('checked', false);
                toastr.error('<?php echo get_phrase('error_granting_permission'); ?>');
            }
        });
    }
    
    function revokePermission(userId, userType, module, permission, $checkbox) {
        showStatus('<?php echo get_phrase('revoking_permission'); ?>...', 'info');
        
        $.ajax({
            url: '<?php echo site_url('user_permissions/revoke_permission'); ?>',
            method: 'POST',
            data: {
                user_id: userId,
                user_type: userType,
                module_name: module,
                permission_type: permission
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showStatus('<?php echo get_phrase('permission_revoked_successfully'); ?>', 'success');
                    // Use toastr for non-intrusive notification
                    toastr.success(response.message || '<?php echo get_phrase('permission_revoked_successfully'); ?>');
                    setTimeout(function() {
                        $('#save-status').fadeOut();
                    }, 2000);
                } else {
                    showStatus(response.message, 'error');
                    $checkbox.prop('checked', true);
                    toastr.error(response.message || '<?php echo get_phrase('error_revoking_permission'); ?>');
                }
            },
            error: function() {
                showStatus('<?php echo get_phrase('error_revoking_permission'); ?>', 'error');
                $checkbox.prop('checked', true);
                toastr.error('<?php echo get_phrase('error_revoking_permission'); ?>');
            }
        });
    }
    
    function showStatus(message, type) {
        var $status = $('#save-status');
        var colors = {
            success: {bg: '#d1fae5', text: '#065f46'},
            error: {bg: '#fee2e2', text: '#991b1b'},
            info: {bg: '#dbeafe', text: '#1e40af'}
        };
        
        $status.css({
            'background': colors[type].bg,
            'color': colors[type].text,
            'display': 'block'
        }).text(message).fadeIn();
    }
});
</script>

<style>
    .modern-btn {
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:12px 24px;
        border-radius:8px;
        font-weight:600;
        font-size:14px;
        border:none;
        cursor:pointer;
        transition:all 0.2s;
    }
    
    .modern-btn:hover {
        transform:translateY(-2px);
        box-shadow:0 4px 12px rgba(0,0,0,0.2);
    }
    
    .grant-all-btn:hover,
    .revoke-all-btn:hover {
        transform:translateY(-2px);
        box-shadow:0 2px 8px rgba(0,0,0,0.15);
    }
    
    .grant-all-btn:disabled,
    .revoke-all-btn:disabled {
        opacity:0.5;
        cursor:not-allowed;
    }
    
    tbody tr:hover {
        background:#f9fafb;
    }
</style>
