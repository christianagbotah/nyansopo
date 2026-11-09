<!-- User Permissions List -->
<div class="row">
    <div class="col-md-12">
        
        <!-- Page Header -->
        <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:32px;border-radius:16px;margin-bottom:24px;box-shadow:0 10px 40px rgba(102,126,234,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                <div>
                    <h2 style="color:#fff;margin:0;font-size:28px;font-weight:700;display:flex;align-items:center;gap:12px;">
                        <i class="entypo-users"></i>
                        <?php echo get_phrase(ucfirst($selected_user_type) . 's'); ?> <?php echo get_phrase('permissions'); ?>
                    </h2>
                    <p style="color:rgba(255,255,255,0.9);margin:8px 0 0 0;font-size:15px;">
                        <?php echo get_phrase('manage_permissions_for'); ?> <?php echo get_phrase($selected_user_type . 's'); ?>
                    </p>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="<?php echo site_url('user_permissions'); ?>" class="modern-btn modern-btn-light" style="background:rgba(255,255,255,0.2);color:#fff;text-decoration:none;">
                        <i class="entypo-left-open-big"></i> <?php echo get_phrase('back_to_dashboard'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- User Type Selector -->
        <div style="background:#fff;padding:16px 24px;border-radius:12px;margin-bottom:24px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="<?php echo site_url('user_permissions/list_users/teacher'); ?>" 
                   class="user-type-btn <?php echo $selected_user_type == 'teacher' ? 'active' : ''; ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;transition:all 0.2s;<?php echo $selected_user_type == 'teacher' ? 'background:#667eea;color:#fff;' : 'background:#f3f4f6;color:#6b7280;'; ?>">
                    <i class="entypo-users"></i> <?php echo get_phrase('teachers'); ?>
                </a>
                <a href="<?php echo site_url('user_permissions/list_users/admin'); ?>" 
                   class="user-type-btn <?php echo $selected_user_type == 'admin' ? 'active' : ''; ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;transition:all 0.2s;<?php echo $selected_user_type == 'admin' ? 'background:#667eea;color:#fff;' : 'background:#f3f4f6;color:#6b7280;'; ?>">
                    <i class="entypo-user"></i> <?php echo get_phrase('admins'); ?>
                </a>
                <a href="<?php echo site_url('user_permissions/list_users/accountant'); ?>" 
                   class="user-type-btn <?php echo $selected_user_type == 'accountant' ? 'active' : ''; ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;transition:all 0.2s;<?php echo $selected_user_type == 'accountant' ? 'background:#667eea;color:#fff;' : 'background:#f3f4f6;color:#6b7280;'; ?>">
                    <i class="entypo-calculator"></i> <?php echo get_phrase('accountants'); ?>
                </a>
            </div>
        </div>

        <!-- Users Table -->
        <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;">
            <div style="padding:24px;border-bottom:2px solid #f3f4f6;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                    <h4 style="margin:0;font-size:18px;font-weight:600;color:#1f2937;">
                        <i class="entypo-list"></i> <?php echo get_phrase('users_list'); ?>
                    </h4>
                    <div style="display:flex;gap:8px;">
                        <input type="text" id="search-users" placeholder="<?php echo get_phrase('search_users'); ?>..." 
                               style="padding:10px 16px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;width:250px;">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="users-table" style="margin:0;">
                    <thead style="background:#f9fafb;">
                        <tr>
                            <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('name'); ?></th>
                            <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('email'); ?></th>
                            <?php if ($selected_user_type == 'admin'): ?>
                                <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('level'); ?></th>
                            <?php endif; ?>
                            <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('permissions_count'); ?></th>
                            <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('modules_access'); ?></th>
                            <th class="text-center" style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr style="transition:background 0.2s;">
                                    <td style="padding:16px;vertical-align:middle;">
                                        <div style="display:flex;align-items:center;gap:12px;">
                                            <div style="width:40px;height:40px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#1e40af;font-size:16px;">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:600;color:#1f2937;font-size:14px;">
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:16px;vertical-align:middle;">
                                        <span style="font-size:14px;color:#6b7280;">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </span>
                                    </td>
                                    <?php if ($selected_user_type == 'admin'): ?>
                                        <td style="padding:16px;vertical-align:middle;">
                                            <?php 
                                                $level_labels = [1 => 'Super Admin', 2 => 'Admin', 3 => 'Assistant', 4 => 'Cashier'];
                                                $level = isset($user['level']) ? $user['level'] : 0;
                                            ?>
                                            <span style="display:inline-block;background:#fef3c7;color:#92400e;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600;">
                                                <?php echo isset($level_labels[$level]) ? $level_labels[$level] : 'Level ' . $level; ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td style="padding:16px;vertical-align:middle;">
                                        <span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:6px 14px;border-radius:20px;font-weight:700;font-size:14px;">
                                            <?php echo $user['permissions_count']; ?>
                                        </span>
                                    </td>
                                    <td style="padding:16px;vertical-align:middle;">
                                        <?php if (!empty($user['modules'])): ?>
                                            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                                <?php 
                                                    $display_limit = 3;
                                                    $total_modules = count($user['modules']);
                                                    $displayed = 0;
                                                ?>
                                                <?php foreach ($user['modules'] as $module): ?>
                                                    <?php if ($displayed < $display_limit): ?>
                                                        <span style="display:inline-block;background:#e5e7eb;color:#374151;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600;">
                                                            <?php echo htmlspecialchars(substr($module['module_display_name'], 0, 20)); ?>
                                                        </span>
                                                        <?php $displayed++; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if ($total_modules > $display_limit): ?>
                                                    <span style="display:inline-block;background:#667eea;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600;">
                                                        +<?php echo ($total_modules - $display_limit); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size:13px;color:#9ca3af;font-style:italic;">
                                                <?php echo get_phrase('no_permissions'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="padding:16px;vertical-align:middle;">
                                        <a href="<?php echo site_url('user_permissions/manage_user/' . $selected_user_type . '/' . $user['user_id']); ?>" 
                                           class="action-btn" 
                                           title="<?php echo get_phrase('manage_permissions'); ?>"
                                           style="display:inline-block;padding:10px 16px;border:none;border-radius:8px;background:#667eea;color:#fff;cursor:pointer;transition:all 0.2s;font-size:14px;text-decoration:none;font-weight:600;">
                                            <i class="entypo-lock"></i> <?php echo get_phrase('manage'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $selected_user_type == 'admin' ? 6 : 5; ?>" class="text-center" style="padding:48px;">
                                    <div style="color:#9ca3af;">
                                        <i class="entypo-users" style="font-size:48px;display:block;margin-bottom:16px;"></i>
                                        <p style="font-size:16px;margin:0;font-weight:500;"><?php echo get_phrase('no_users_found'); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
$(document).ready(function() {
    // Search functionality
    $('#search-users').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#users-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Hover effects
    $('.user-type-btn:not(.active)').hover(
        function() {
            $(this).css({'background': '#e5e7eb', 'color': '#374151'});
        },
        function() {
            $(this).css({'background': '#f3f4f6', 'color': '#6b7280'});
        }
    );
});
</script>

<style>
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102,126,234,0.4);
    }
    
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
</style>
