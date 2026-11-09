<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
?>

<style>
/* Professional Header */
.providers-header-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e8eaf0;
}

.providers-header-card h2 {
    margin: 0 0 6px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1a202c;
    letter-spacing: -0.02em;
}

.providers-header-card h2 i {
    font-size: 24px;
    color: #5a67d8;
    margin-right: 12px;
}

.providers-header-card p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
    font-weight: 400;
}

.add-provider-btn {
    background: #5a67d8;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    cursor: pointer;
}

.add-provider-btn:hover {
    background: #4c51bf;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

.add-provider-btn i {
    margin-right: 6px;
}

/* Professional Table Card */
.providers-table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e8eaf0;
    overflow: hidden;
}

.providers-table-card .table-responsive {
    padding: 0;
}

#providersTable {
    margin-bottom: 0 !important;
}

#providersTable thead th {
    background: #f8f9fc;
    color: #475569 !important;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    padding: 14px 16px;
    border: none !important;
    border-bottom: 2px solid #e8eaf0 !important;
}

#providersTable tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f1f3f5;
}

#providersTable tbody tr:hover {
    background: #f8f9fc;
}

#providersTable tbody tr:last-child {
    border-bottom: none;
}

#providersTable tbody td {
    padding: 16px;
    vertical-align: middle;
    border: none !important;
    color: #334155;
    font-size: 14px;
}

/* Subtle Badges */
.provider-status-badge {
    padding: 5px 12px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.provider-status-active {
    background: #d1fae5;
    color: #065f46;
}

.provider-status-active i {
    color: #10b981;
}

.provider-status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.provider-status-inactive i {
    color: #ef4444;
}

.provider-code-badge {
    background: #ede9fe;
    color: #5b21b6;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    font-family: 'Courier New', monospace;
}

.staff-count-badge {
    background: #f3f4f6;
    color: #374151;
    padding: 5px 12px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.staff-count-badge i {
    color: #6b7280;
}

/* 3-Dot Dropdown Menu */
.action-dropdown {
    position: relative;
    display: inline-block;
}

.action-dots-btn {
    background: #5a67d8;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-dots-btn:hover {
    background: #4c51bf;
    transform: scale(1.05);
}

.action-dots-btn i {
    color: #ffffff !important;
    font-size: 16px;
}

.action-menu {
    position: absolute;
    right: 0;
    top: 38px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    border: 1px solid #e8eaf0;
    min-width: 160px;
    z-index: 1000;
    display: none;
    overflow: hidden;
}

.action-menu.show {
    display: block;
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.action-menu-item {
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}

.action-menu-item:hover {
    background: #f8f9fc;
}

.action-menu-item i {
    font-size: 14px;
    width: 16px;
    text-align: center;
}

.action-menu-item.edit-item i {
    color: #5a67d8;
}

.action-menu-item.toggle-item i {
    color: #f59e0b;
}

.action-menu-item.delete-item i {
    color: #ef4444;
}

.action-menu-item.disabled-item {
    opacity: 0.5;
    cursor: not-allowed;
}

.action-menu-item.disabled-item:hover {
    background: white;
}

.action-menu-divider {
    height: 1px;
    background: #e8eaf0;
    margin: 4px 0;
}

.contact-info {
    font-size: 13px;
    line-height: 1.8;
    color: #64748b;
}

.contact-info i {
    width: 14px;
    color: #94a3b8;
    font-size: 12px;
}

.provider-name {
    color: #1a202c;
    font-weight: 500;
    font-size: 14px;
}
</style>

<div class="row">
    <div class="col-md-12">
        <!-- Professional Header Card -->
        <div class="providers-header-card">
            <div class="row">
                <div class="col-md-8">
                    <h2>
                        <i class="fa fa-building"></i>
                        Tier 2 Pension Providers
                    </h2>
                    <p>Manage tier 2 pension provider organizations and assignments</p>
                </div>
                <div class="col-md-4 text-right">
                    <button class="add-provider-btn" onclick="showAddModal()">
                        <i class="fa fa-plus"></i> Add Provider
                    </button>
                </div>
            </div>
        </div>

        <!-- Professional Table Card -->
        <div class="providers-table-card">
            <div class="table-responsive">
                <table id="providersTable" class="table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Provider Name</th>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Staff</th>
                            <th width="80">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php
                            $providers = $this->db->get('pension_tier2_providers')->result_array();
                            foreach ($providers as $provider):
                                // Get staff count
                                $this->db->where('tier2_provider_id', $provider['provider_id']);
                                $teacher_count = $this->db->get('teacher')->num_rows();
                                
                                $this->db->where('tier2_provider_id', $provider['provider_id']);
                                $admin_count = $this->db->get('admin')->num_rows();
                                
                                $this->db->where('tier2_provider_id', $provider['provider_id']);
                                $staff_count = $this->db->get('non_teaching_staff')->num_rows();
                                
                                $total_staff = $teacher_count + $admin_count + $staff_count;
                            ?>
                            <tr>
                                <td><span class="provider-name"><?php echo $provider['provider_name']; ?></span></td>
                                <td><span class="provider-code-badge"><?php echo $provider['provider_code']; ?></span></td>
                                <td><?php echo isset($provider['description']) ? $provider['description'] : '-'; ?></td>
                                <td>
                                    <div class="contact-info">
                                        <?php if (isset($provider['provider_email']) && $provider['provider_email']): ?>
                                            <div><i class="fa fa-envelope"></i> <?php echo $provider['provider_email']; ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($provider['provider_phone']) && $provider['provider_phone']): ?>
                                            <div><i class="fa fa-phone"></i> <?php echo $provider['provider_phone']; ?></div>
                                        <?php endif; ?>
                                        <?php if ((!isset($provider['provider_email']) || !$provider['provider_email']) && (!isset($provider['provider_phone']) || !$provider['provider_phone'])): ?>
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($provider['is_active'] == 1): ?>
                                        <span class="provider-status-badge provider-status-active">
                                            <i class="fa fa-check-circle"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="provider-status-badge provider-status-inactive">
                                            <i class="fa fa-times-circle"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="staff-count-badge">
                                        <i class="fa fa-users"></i> <?php echo $total_staff; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-dropdown">
                                        <button class="action-dots-btn" onclick="toggleActionMenu(event, this)">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </button>
                                        <div class="action-menu">
                                            <button class="action-menu-item edit-item" onclick="showEditModal(<?php echo $provider['provider_id']; ?>); closeAllMenus();">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                            <button class="action-menu-item toggle-item" onclick="toggleStatus(<?php echo $provider['provider_id']; ?>, <?php echo $provider['is_active']; ?>); closeAllMenus();">
                                                <i class="fa fa-power-off"></i> <?php echo $provider['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                            <div class="action-menu-divider"></div>
                                            <?php if ($total_staff == 0): ?>
                                            <button class="action-menu-item delete-item" onclick="deleteProvider(<?php echo $provider['provider_id']; ?>); closeAllMenus();">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                            <?php else: ?>
                                            <button class="action-menu-item delete-item disabled-item" disabled title="Cannot delete provider with assigned staff">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize DataTable with modern styling
    $('#providersTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });
    
    // Close dropdown menus when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.action-dropdown').length) {
            closeAllMenus();
        }
    });
});

// Toggle action dropdown menu
function toggleActionMenu(event, button) {
    event.stopPropagation();
    var menu = $(button).siblings('.action-menu');
    var isOpen = menu.hasClass('show');
    
    // Close all menus first
    closeAllMenus();
    
    // Toggle this menu
    if (!isOpen) {
        menu.addClass('show');
    }
}

// Close all dropdown menus
function closeAllMenus() {
    $('.action-menu').removeClass('show');
}

// Show Add Modal using AJAX modal system
function showAddModal() {
    showAjaxModal('<?php echo site_url("modal/modal_pension_provider_add"); ?>');
}

// Show Edit Modal using AJAX modal system
function showEditModal(providerId) {
    showAjaxModal('<?php echo site_url("modal/modal_pension_provider_edit/"); ?>' + providerId);
}

// Toggle Provider Status
function toggleStatus(providerId, currentStatus) {
    var action = currentStatus == 1 ? 'Deactivate' : 'Activate';
    var message = 'Are you sure you want to ' + action.toLowerCase() + ' this provider?';
    
    showConfirmModal(
        'Confirm Action',
        message,
        function() {
            showAjaxModal_alert('Processing...', 'Loading', false);
            
            $.ajax({
                url: '<?php echo site_url("admin/pension_providers/toggle_status/"); ?>' + providerId,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.message == 'done') {
                        showAjaxModal_alert('Provider status updated successfully', 'Success', false);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAjaxModal_alert('Error updating status', 'Error', false);
                    }
                },
                error: function() {
                    showAjaxModal_alert('Error updating status', 'Error', false);
                }
            });
        },
        action,
        'warning'
    );
}

// Delete Provider
function deleteProvider(providerId) {
    showConfirmModal(
        'Confirm Deletion',
        'Are you sure you want to delete this provider?<br><br><strong style="color: #ef4444;">This action cannot be undone.</strong>',
        function() {
            showAjaxModal_alert('Processing...', 'Loading', false);
            
            $.ajax({
                url: '<?php echo site_url("admin/pension_providers/delete/"); ?>' + providerId,
                type: 'POST',
                data: {
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.message == 'done') {
                        showAjaxModal_alert('Provider deleted successfully', 'Success', false);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAjaxModal_alert(response.errors || 'Error deleting provider', 'Error', false);
                    }
                },
                error: function() {
                    showAjaxModal_alert('Error deleting provider', 'Error', false);
                }
            });
        },
        'Delete',
        'danger'
    );
}
</script>
