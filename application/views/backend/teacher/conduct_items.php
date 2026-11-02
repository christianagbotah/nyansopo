<!-- Conduct Items Management - Modern UI -->
<div class="row">
    <div class="col-md-12">
        
        <!-- Page Header -->
        <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:32px;border-radius:16px;margin-bottom:24px;box-shadow:0 10px 40px rgba(102,126,234,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                <div>
                    <h2 style="color:#fff;margin:0;font-size:28px;font-weight:700;display:flex;align-items:center;gap:12px;">
                        <i class="entypo-list"></i>
                        <?php echo get_phrase('manage_conduct_items'); ?>
                    </h2>
                    <p style="color:rgba(255,255,255,0.9);margin:8px 0 0 0;font-size:15px;">
                        <?php echo get_phrase('configure_conduct_items_for_report_cards'); ?>
                    </p>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button class="modern-btn modern-btn-light" id="add-conduct-item" style="background:#fff;color:#667eea;">
                        <i class="entypo-plus"></i> <?php echo get_phrase('add_new_item'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Instructions Card -->
        <div style="background:#fff;padding:24px;border-radius:12px;margin-bottom:24px;border:2px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
            <div style="display:flex;align-items:start;gap:16px;">
                <div style="width:48px;height:48px;background:#dbeafe;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="entypo-info" style="font-size:24px;color:#3b82f6;"></i>
                </div>
                <div style="flex:1;">
                    <h4 style="margin:0 0 12px 0;font-size:16px;font-weight:600;color:#1f2937;">
                        <?php echo get_phrase('instructions'); ?>
                    </h4>
                    <ul style="margin:0;padding-left:20px;color:#6b7280;font-size:14px;line-height:1.8;">
                        <li><strong><?php echo get_phrase('drag_to_reorder'); ?>:</strong> Click and drag the <i class="entypo-menu"></i> icon to reorder items</li>
                        <li><strong><?php echo get_phrase('toggle_status'); ?>:</strong> Click the eye icon to activate/deactivate items</li>
                        <li><strong><?php echo get_phrase('visibility'); ?>:</strong> Inactive items are hidden from teachers but retained in database</li>
                        <li><strong><?php echo get_phrase('edit_delete'); ?>:</strong> Use pencil icon to edit, trash icon to delete</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Items Table Card -->
        <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;">
            <div style="padding:24px;border-bottom:2px solid #f3f4f6;">
                <h4 style="margin:0;font-size:18px;font-weight:600;color:#1f2937;">
                    <i class="entypo-list"></i> <?php echo get_phrase('conduct_items'); ?>
                </h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="conduct-items-table" style="margin:0;">
                    <thead style="background:#f9fafb;">
                        <tr>
                            <th width="50" style="padding:16px;"><i class="entypo-menu"></i></th>
                            <th width="100" style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('order'); ?></th>
                            <th style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('conduct_item_name'); ?></th>
                            <th width="120" style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('status'); ?></th>
                            <th width="140" class="text-center" style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('created_at'); ?></th>
                            <th width="160" class="text-center" style="padding:16px;font-weight:600;color:#374151;font-size:14px;"><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="sortable-conduct-items">
                        <?php if (!empty($conduct_items)): ?>
                            <?php foreach ($conduct_items as $item): ?>
                                <tr data-id="<?php echo $item->id; ?>" data-order="<?php echo $item->display_order; ?>" style="transition:background 0.2s;">
                                    <td class="drag-handle" style="cursor:move;padding:16px;vertical-align:middle;">
                                        <i class="entypo-menu" style="font-size:18px;color:#9ca3af;"></i>
                                    </td>
                                    <td style="padding:16px;vertical-align:middle;">
                                        <div style="display:flex;align-items:center;gap:12px;">
                                            <span class="order-badge" style="display:inline-block;background:#dbeafe;color:#1e40af;padding:6px 14px;border-radius:20px;font-weight:700;font-size:15px;min-width:50px;text-align:center;">
                                                #<?php echo $item->display_order; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="padding:16px;vertical-align:middle;">
                                        <span style="font-size:15px;font-weight:500;color:#1f2937;line-height:1.5;">
                                            <?php echo htmlspecialchars($item->name); ?>
                                        </span>
                                    </td>
                                    <td class="status-cell" style="padding:16px;vertical-align:middle;">
                                        <?php if ($item->is_active): ?>
                                            <span class="status-badge status-active" style="display:inline-flex;align-items:center;gap:6px;background:#d1fae5;color:#065f46;padding:8px 16px;border-radius:20px;font-weight:600;font-size:13px;white-space:nowrap;">
                                                <i class="entypo-check"></i> <?php echo get_phrase('active'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive" style="display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;color:#6b7280;padding:8px 16px;border-radius:20px;font-weight:600;font-size:13px;white-space:nowrap;">
                                                <i class="entypo-cancel"></i> <?php echo get_phrase('inactive'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="padding:16px;vertical-align:middle;">
                                        <small style="color:#6b7280;font-size:13px;white-space:nowrap;">
                                            <?php echo date('d M Y', strtotime($item->created_at)); ?>
                                        </small>
                                    </td>
                                    <td class="text-center" style="padding:16px;vertical-align:middle;">
                                        <div class="btn-group" style="display:flex;gap:8px;justify-content:center;">
                                            <button class="action-btn toggle-conduct-status" 
                                                    data-id="<?php echo $item->id; ?>"
                                                    data-status="<?php echo $item->is_active; ?>"
                                                    data-name="<?php echo htmlspecialchars($item->name); ?>"
                                                    title="<?php echo $item->is_active ? get_phrase('deactivate') : get_phrase('activate'); ?>"
                                                    style="padding:10px 14px;border:none;border-radius:8px;background:#f3f4f6;color:#6b7280;cursor:pointer;transition:all 0.2s;font-size:15px;">
                                                <i class="entypo-<?php echo $item->is_active ? 'cancel' : 'eye'; ?>"></i>
                                            </button>
                                            <button class="action-btn edit-conduct-item" 
                                                    data-id="<?php echo $item->id; ?>"
                                                    title="<?php echo get_phrase('edit'); ?>"
                                                    style="padding:10px 14px;border:none;border-radius:8px;background:#dbeafe;color:#1e40af;cursor:pointer;transition:all 0.2s;font-size:15px;">
                                                <i class="entypo-pencil"></i>
                                            </button>
                                            <button class="action-btn delete-conduct-item" 
                                                    data-id="<?php echo $item->id; ?>"
                                                    data-name="<?php echo htmlspecialchars($item->name); ?>"
                                                    title="<?php echo get_phrase('delete'); ?>"
                                                    style="padding:10px 14px;border:none;border-radius:8px;background:#fee2e2;color:#991b1b;cursor:pointer;transition:all 0.2s;font-size:15px;">
                                                <i class="entypo-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding:48px;">
                                    <div style="color:#9ca3af;">
                                        <i class="entypo-info" style="font-size:48px;display:block;margin-bottom:16px;"></i>
                                        <p style="font-size:16px;margin:0;font-weight:500;"><?php echo get_phrase('no_conduct_items_found'); ?></p>
                                        <p style="font-size:14px;margin:8px 0 0 0;"><?php echo get_phrase('click_add_to_create_first_item'); ?></p>
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

<!-- Load jQuery UI for Sortable (since it's disabled globally) -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>

<!-- JavaScript for Conduct Items Management -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Helper function to ensure modal backdrop is removed
    function cleanupModalBackdrop() {
        // Remove any lingering backdrops
        $('.modal-backdrop').remove();
        // Remove modal-open class from body
        $('body').removeClass('modal-open');
        // Reset body padding
        $('body').css('padding-right', '');
    }
    
    // Initialize jQuery UI Sortable for drag-and-drop reordering
    $("#sortable-conduct-items").sortable({
        handle: ".drag-handle",
        axis: "y",
        placeholder: "ui-state-highlight",
        helper: function(e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index) {
                $(this).width($originals.eq(index).width());
            });
            return $helper;
        },
        start: function(e, ui) {
            ui.placeholder.height(ui.item.height());
            ui.item.css('opacity', '0.6');
        },
        stop: function(e, ui) {
            ui.item.css('opacity', '1');
        },
        update: function(event, ui) {
            var orderMap = {};
            var newOrder = 1;
            
            // Build order map
            $("#sortable-conduct-items tr").each(function() {
                if ($(this).data('id')) {
                    var itemId = $(this).data('id');
                    orderMap[itemId] = newOrder;
                    // Update order badge
                    $(this).find('.order-badge').text('#' + newOrder);
                    newOrder++;
                }
            });
            
            // Send AJAX request to save new order
            $.ajax({
                url: '<?php echo site_url('admin/conduct_items/reorder'); ?>',
                type: 'POST',
                data: { order_map: orderMap },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success(response.message || '<?php echo get_phrase('order_updated_successfully'); ?>');
                    } else {
                        toastr.error(response.message || '<?php echo get_phrase('error_updating_order'); ?>');
                        // Don't reload - just show error, order remains as dragged
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('<?php echo get_phrase('error_updating_order'); ?>');
                    // Don't reload - just show error, order remains as dragged
                }
            });
        }
    });
    
    // Add new conduct item
    $("#add-conduct-item").click(function() {
        $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
        $('#modal_ajax').modal('show', {backdrop: 'static'});
        
        $.ajax({
            url: '<?php echo site_url('admin/conduct_items/get_form'); ?>',
            type: 'POST',
            success: function(response) {
                $('#modal_ajax .modal-body').html(response);
            },
            error: function() {
                $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Form</h4><p>Unable to load the form.</p></div>');
            }
        });
    });
    
    // Edit conduct item
    $(document).on('click', '.edit-conduct-item', function() {
        var itemId = $(this).data('id');
        
        $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
        $('#modal_ajax').modal('show', {backdrop: 'static'});
        
        $.ajax({
            url: '<?php echo site_url('admin/conduct_items/get_form'); ?>',
            type: 'POST',
            data: { id: itemId },
            success: function(response) {
                $('#modal_ajax .modal-body').html(response);
            },
            error: function() {
                $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Form</h4><p>Unable to load the form.</p></div>');
            }
        });
    });
    
    // Toggle active status
    $(document).on('click', '.toggle-conduct-status', function() {
        var itemId = $(this).data('id');
        var itemName = $(this).data('name');
        var currentStatus = $(this).data('status');
        var $button = $(this);
        var $row = $button.closest('tr');
        var newStatusText = currentStatus == 1 ? '<?php echo get_phrase('deactivate'); ?>' : '<?php echo get_phrase('activate'); ?>';
        
        // Use confirm_modal.php's showConfirmModal
        showConfirmModal(
            '<?php echo get_phrase('confirm_action'); ?>',
            '<?php echo get_phrase('are_you_sure_you_want_to'); ?> <strong>' + newStatusText.toLowerCase() + '</strong> "<strong>' + itemName + '</strong>"?',
            function() {
                // Show loading modal
                showAjaxModal_alert('<?php echo get_phrase('processing'); ?>...', 'loading', false, false);
                
                // User clicked Confirm - proceed with toggle
                $.ajax({
                    url: '<?php echo site_url('admin/conduct_items/toggle'); ?>/' + itemId,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        // Hide loading modal and cleanup backdrop
                        $('#modal_alert').modal('hide');
                        cleanupModalBackdrop();
                        
                        if (response.status === 'success') {
                            // Update UI - status badge
                            var $statusCell = $row.find('.status-cell');
                            if (response.new_status == 1) {
                                // Item is now active
                                $statusCell.html('<span class="status-badge status-active" style="display:inline-flex;align-items:center;gap:6px;background:#d1fae5;color:#065f46;padding:8px 16px;border-radius:20px;font-weight:600;font-size:13px;white-space:nowrap;"><i class="entypo-check"></i> <?php echo get_phrase('active'); ?></span>');
                                $button.find('i').removeClass('entypo-eye').addClass('entypo-eye-off');
                                $button.attr('title', '<?php echo get_phrase('deactivate'); ?>');
                            } else {
                                // Item is now inactive
                                $statusCell.html('<span class="status-badge status-inactive" style="display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;color:#6b7280;padding:8px 16px;border-radius:20px;font-weight:600;font-size:13px;white-space:nowrap;"><i class="entypo-cancel"></i> <?php echo get_phrase('inactive'); ?></span>');
                                $button.find('i').removeClass('entypo-eye-off').addClass('entypo-eye');
                                $button.attr('title', '<?php echo get_phrase('activate'); ?>');
                            }
                            $button.data('status', response.new_status);
                            
                            // Show success toastr instead of modal to avoid backdrop
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message || '<?php echo get_phrase('error_toggling_status'); ?>');
                        }
                    },
                    error: function() {
                        $('#modal_alert').modal('hide');
                        cleanupModalBackdrop();
                        toastr.error('<?php echo get_phrase('error_toggling_status'); ?>');
                    }
                });
            },
            '<?php echo get_phrase('confirm'); ?>',
            'warning'
        );
    });
    
    // Delete conduct item
    $(document).on('click', '.delete-conduct-item', function() {
        var itemId = $(this).data('id');
        var itemName = $(this).data('name');
        var $row = $(this).closest('tr');
        
        // Use confirm_modal.php's showConfirmModal
        showConfirmModal(
            '<?php echo get_phrase('delete_confirmation'); ?>',
            '<?php echo get_phrase('are_you_sure_you_want_to_delete'); ?> "<strong>' + itemName + '</strong>"?<br><br><span style="color:#e74c3c;"><?php echo get_phrase('this_action_cannot_be_undone'); ?></span>',
            function() {
                // Show loading modal
                showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>...', 'loading', false, false);
                
                // User clicked Confirm - proceed with deletion
                $.ajax({
                    url: '<?php echo site_url('admin/conduct_items/delete'); ?>/' + itemId,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Show success modal that stays visible during reload
                            showAjaxModal_alert(response.message || '<?php echo get_phrase('item_deleted_successfully'); ?>', 'success', false, false);
                            
                            // Fade out and remove row
                            $row.fadeOut(400, function() {
                                $(this).remove();
                                // Check if table is empty
                                if ($('#sortable-conduct-items tr[data-id]').length === 0) {
                                    $('#sortable-conduct-items').html('<tr><td colspan="6" class="text-center" style="padding:48px;"><div style="color:#9ca3af;"><i class="entypo-info" style="font-size:48px;display:block;margin-bottom:16px;"></i><p style="font-size:16px;margin:0;font-weight:500;"><?php echo get_phrase('no_conduct_items_found'); ?></p><p style="font-size:14px;margin:8px 0 0 0;"><?php echo get_phrase('click_add_to_create_first_item'); ?></p></div></td></tr>');
                                }
                                
                                // Close success modal after row removal
                                setTimeout(function() {
                                    $('#modal_alert').modal('hide');
                                }, 1000);
                            });
                        } else {
                            $('#modal_alert').modal('hide');
                            showAjaxModal_alert(response.message || '<?php echo get_phrase('error_deleting_item'); ?>', 'error', false, false);
                        }
                    },
                    error: function() {
                        $('#modal_alert').modal('hide');
                        showAjaxModal_alert('<?php echo get_phrase('error_deleting_item'); ?>', 'error', false, false);
                    }
                });
            },
            '<?php echo get_phrase('delete'); ?>',
            'danger'
        );
    });
    
});
</script>

<style>
/* Modern Conduct Items Styling */
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.action-btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

#sortable-conduct-items tr:hover {
    background: #f9fafb !important;
}

#sortable-conduct-items tr {
    cursor: default;
}

#sortable-conduct-items .drag-handle {
    cursor: move !important;
}

#sortable-conduct-items .ui-sortable-helper {
    background-color: #fff !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
    opacity: 0.9;
}

#sortable-conduct-items .ui-state-highlight {
    height: 60px;
    background-color: #dbeafe !important;
    border: 2px dashed #3b82f6 !important;
}

.modern-btn-light {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    font-size: 14px;
}

.modern-btn-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

/* Better text rendering */
#conduct-items-table {
    font-size: 14px;
}

#conduct-items-table thead th {
    letter-spacing: 0.025em;
    text-transform: uppercase;
    font-size: 12px;
}

#conduct-items-table tbody td {
    font-size: 14px;
    line-height: 1.6;
}

.order-badge {
    letter-spacing: 0.05em;
}

.status-badge {
    letter-spacing: 0.025em;
    text-transform: uppercase;
    font-size: 11px !important;
    font-weight: 700 !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto;
    }
    
    #conduct-items-table th,
    #conduct-items-table td {
        padding: 12px 8px !important;
        font-size: 13px;
    }
    
    .action-btn {
        padding: 8px 10px !important;
        font-size: 14px !important;
    }
    
    .order-badge {
        padding: 4px 10px !important;
        font-size: 13px !important;
    }
    
    .status-badge {
        padding: 6px 12px !important;
        font-size: 10px !important;
    }
}

@media (max-width: 480px) {
    #conduct-items-table {
        font-size: 12px;
    }
    
    #conduct-items-table thead th {
        font-size: 11px;
    }
}
</style>
