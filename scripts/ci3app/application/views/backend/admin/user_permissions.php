<!-- User Permissions Management - Dashboard -->
<div class="row">
    <div class="col-md-12">
        
        <!-- Page Header -->
        <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:32px;border-radius:16px;margin-bottom:24px;box-shadow:0 10px 40px rgba(102,126,234,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                <div>
                    <h2 style="color:#fff;margin:0;font-size:28px;font-weight:700;display:flex;align-items:center;gap:12px;">
                        <i class="entypo-lock"></i>
                        <?php echo get_phrase('user_permissions_management'); ?>
                    </h2>
                    <p style="color:rgba(255,255,255,0.9);margin:8px 0 0 0;font-size:15px;">
                        <?php echo get_phrase('grant_and_manage_user_permissions_across_modules'); ?>
                    </p>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="<?php echo site_url('user_permissions/list_users/teacher'); ?>" class="modern-btn modern-btn-light" style="background:#fff;color:#667eea;text-decoration:none;">
                        <i class="entypo-users"></i> <?php echo get_phrase('manage_users'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row" style="margin-bottom:24px;">
            <div class="col-md-3 col-sm-6">
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border-left:4px solid #10b981;">
                    <div style="display:flex;justify-content:space-between;align-items:start;">
                        <div>
                            <p style="margin:0;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                                <?php echo get_phrase('active_permissions'); ?>
                            </p>
                            <h3 style="margin:8px 0 0 0;font-size:32px;font-weight:700;color:#1f2937;">
                                <?php echo $statistics['total_active']; ?>
                            </h3>
                        </div>
                        <div style="width:48px;height:48px;background:#d1fae5;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="entypo-check" style="font-size:24px;color:#10b981;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border-left:4px solid #ef4444;">
                    <div style="display:flex;justify-content:space-between;align-items:start;">
                        <div>
                            <p style="margin:0;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                                <?php echo get_phrase('revoked_permissions'); ?>
                            </p>
                            <h3 style="margin:8px 0 0 0;font-size:32px;font-weight:700;color:#1f2937;">
                                <?php echo $statistics['total_revoked']; ?>
                            </h3>
                        </div>
                        <div style="width:48px;height:48px;background:#fee2e2;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="entypo-cancel" style="font-size:24px;color:#ef4444;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border-left:4px solid #3b82f6;">
                    <div style="display:flex;justify-content:space-between;align-items:start;">
                        <div>
                            <p style="margin:0;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                                <?php echo get_phrase('total_modules'); ?>
                            </p>
                            <h3 style="margin:8px 0 0 0;font-size:32px;font-weight:700;color:#1f2937;">
                                <?php echo count($modules); ?>
                            </h3>
                        </div>
                        <div style="width:48px;height:48px;background:#dbeafe;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="entypo-folder" style="font-size:24px;color:#3b82f6;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border-left:4px solid #f59e0b;">
                    <div style="display:flex;justify-content:space-between;align-items:start;">
                        <div>
                            <p style="margin:0;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                                <?php echo get_phrase('user_types'); ?>
                            </p>
                            <h3 style="margin:8px 0 0 0;font-size:32px;font-weight:700;color:#1f2937;">
                                <?php echo count($statistics['by_user_type']); ?>
                            </h3>
                        </div>
                        <div style="width:48px;height:48px;background:#fef3c7;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="entypo-users" style="font-size:24px;color:#f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules Overview -->
        <div class="row">
            <div class="col-md-8">
                <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:24px;">
                    <div style="padding:24px;border-bottom:2px solid #f3f4f6;">
                        <h4 style="margin:0;font-size:18px;font-weight:600;color:#1f2937;">
                            <i class="entypo-folder"></i> <?php echo get_phrase('available_modules'); ?>
                        </h4>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" style="margin:0;">
                            <thead style="background:#f9fafb;">
                                <tr>
                                    <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('module'); ?></th>
                                    <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('permissions'); ?></th>
                                    <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('users_with_access'); ?></th>
                                    <th class="text-center" style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module): ?>
                                    <?php 
                                        $module_users_count = 0;
                                        foreach ($statistics['by_module'] as $stat) {
                                            if ($stat['module_name'] == $module['module_name']) {
                                                $module_users_count = $stat['count'];
                                                break;
                                            }
                                        }
                                    ?>
                                    <tr style="transition:background 0.2s;">
                                        <td style="padding:16px;vertical-align:middle;">
                                            <div>
                                                <div style="font-weight:600;color:#1f2937;font-size:15px;margin-bottom:4px;">
                                                    <?php echo htmlspecialchars($module['module_display_name']); ?>
                                                </div>
                                                <div style="font-size:13px;color:#6b7280;">
                                                    <?php echo htmlspecialchars($module['module_description']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding:16px;vertical-align:middle;">
                                            <?php 
                                                $perms = explode(',', $module['available_permissions']);
                                                foreach ($perms as $perm): 
                                            ?>
                                                <span style="display:inline-block;background:#e5e7eb;color:#374151;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;margin:2px;">
                                                    <?php echo trim($perm); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td style="padding:16px;vertical-align:middle;">
                                            <span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:6px 14px;border-radius:20px;font-weight:700;font-size:14px;">
                                                <?php echo $module_users_count; ?>
                                            </span>
                                        </td>
                                        <td class="text-center" style="padding:16px;vertical-align:middle;">
                                            <a href="<?php echo site_url('user_permissions/module_overview/' . $module['module_name']); ?>" 
                                               class="action-btn" 
                                               title="<?php echo get_phrase('view_details'); ?>"
                                               style="display:inline-block;padding:10px 14px;border:none;border-radius:8px;background:#dbeafe;color:#1e40af;cursor:pointer;transition:all 0.2s;font-size:15px;text-decoration:none;">
                                                <i class="entypo-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Recent Activity -->
                <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:24px;">
                    <div style="padding:24px;border-bottom:2px solid #f3f4f6;">
                        <h4 style="margin:0;font-size:18px;font-weight:600;color:#1f2937;">
                            <i class="entypo-clock"></i> <?php echo get_phrase('recent_activity'); ?>
                        </h4>
                    </div>
                    
                    <div style="padding:16px;max-height:500px;overflow-y:auto;">
                        <?php if (!empty($recent_activity)): ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <div style="padding:12px;border-bottom:1px solid #f3f4f6;last-child:border-bottom:none;">
                                    <div style="display:flex;gap:12px;">
                                        <div style="flex-shrink:0;width:32px;height:32px;background:<?php echo $activity['status'] == 'active' ? '#d1fae5' : '#fee2e2'; ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                            <i class="entypo-<?php echo $activity['status'] == 'active' ? 'check' : 'cancel'; ?>" style="color:<?php echo $activity['status'] == 'active' ? '#10b981' : '#ef4444'; ?>;font-size:16px;"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:13px;font-weight:600;color:#1f2937;margin-bottom:2px;">
                                                <?php echo htmlspecialchars($activity['user_name']); ?>
                                            </div>
                                            <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">
                                                <?php echo $activity['status'] == 'active' ? get_phrase('granted') : get_phrase('revoked'); ?>: 
                                                <strong><?php echo $activity['permission_type']; ?></strong> on
                                                <strong><?php echo htmlspecialchars($activity['module_display_name']); ?></strong>
                                            </div>
                                            <div style="font-size:11px;color:#9ca3af;">
                                                <?php echo date('M d, Y H:i', strtotime($activity['granted_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:32px;text-align:center;color:#9ca3af;">
                                <i class="entypo-info" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                <p style="margin:0;font-size:14px;"><?php echo get_phrase('no_recent_activity'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;">
                    <div style="padding:24px;border-bottom:2px solid #f3f4f6;">
                        <h4 style="margin:0;font-size:18px;font-weight:600;color:#1f2937;">
                            <i class="entypo-flash"></i> <?php echo get_phrase('quick_actions'); ?>
                        </h4>
                    </div>
                    
                    <div style="padding:16px;">
                        <a href="<?php echo site_url('user_permissions/list_users/teacher'); ?>" 
                           style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;background:#f9fafb;margin-bottom:8px;text-decoration:none;transition:all 0.2s;border:1px solid #e5e7eb;">
                            <div style="width:36px;height:36px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <i class="entypo-users" style="color:#3b82f6;font-size:18px;"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:600;color:#1f2937;font-size:14px;"><?php echo get_phrase('manage_teachers'); ?></div>
                                <div style="font-size:12px;color:#6b7280;"><?php echo get_phrase('grant_permissions_to_teachers'); ?></div>
                            </div>
                            <i class="entypo-right-open-big" style="color:#9ca3af;"></i>
                        </a>

                        <a href="<?php echo site_url('user_permissions/list_users/admin'); ?>" 
                           style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;background:#f9fafb;margin-bottom:8px;text-decoration:none;transition:all 0.2s;border:1px solid #e5e7eb;">
                            <div style="width:36px;height:36px;background:#fef3c7;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <i class="entypo-user" style="color:#f59e0b;font-size:18px;"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:600;color:#1f2937;font-size:14px;"><?php echo get_phrase('manage_admins'); ?></div>
                                <div style="font-size:12px;color:#6b7280;"><?php echo get_phrase('grant_permissions_to_admins'); ?></div>
                            </div>
                            <i class="entypo-right-open-big" style="color:#9ca3af;"></i>
                        </a>

                        <a href="<?php echo site_url('user_permissions/list_users/accountant'); ?>" 
                           style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;background:#f9fafb;text-decoration:none;transition:all 0.2s;border:1px solid #e5e7eb;">
                            <div style="width:36px;height:36px;background:#d1fae5;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <i class="entypo-calculator" style="color:#10b981;font-size:18px;"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:600;color:#1f2937;font-size:14px;"><?php echo get_phrase('manage_accountants'); ?></div>
                                <div style="font-size:12px;color:#6b7280;"><?php echo get_phrase('grant_permissions_to_accountants'); ?></div>
                            </div>
                            <i class="entypo-right-open-big" style="color:#9ca3af;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
        text-decoration:none;
    }
    
    .modern-btn:hover {
        transform:translateY(-2px);
        box-shadow:0 4px 12px rgba(0,0,0,0.2);
    }
</style>
