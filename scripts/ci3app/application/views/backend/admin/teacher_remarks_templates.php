<!-- Modern Teacher Remarks Templates Page -->
<style>
.page-header-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(102,126,234,0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.page-header-content h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-header-content p {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 15px;
}

.page-header-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.modern-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.btn-modern {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

.btn-modern-light {
    background: #fff;
    color: #667eea;
}

.btn-modern-light:hover {
    background: #f8f9ff;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modern-table thead th {
    padding: 1rem;
    font-weight: 600;
    text-align: left;
    border: none;
    font-size: 14px;
}

.modern-table thead th:first-child {
    border-radius: 8px 0 0 0;
}

.modern-table thead th:last-child {
    border-radius: 0 8px 0 0;
}

.modern-table tbody tr {
    border-bottom: 1px solid #e2e8f0;
    transition: background-color 0.2s;
}

.modern-table tbody tr:hover {
    background-color: #f7fafc;
}

.modern-table tbody td {
    padding: 1rem;
    font-size: 14px;
}

.drag-handle {
    cursor: move;
    color: #cbd5e0;
    transition: color 0.2s;
}

.drag-handle:hover {
    color: #667eea;
}

.category-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.813rem;
    font-weight: 600;
    text-transform: uppercase;
}

.category-positive {
    background-color: #d1fae5;
    color: #065f46;
}

.category-neutral {
    background-color: #dbeafe;
    color: #1e40af;
}

.category-negative {
    background-color: #fee2e2;
    color: #991b1b;
}

.category-none {
    background-color: #f3f4f6;
    color: #6b7280;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.813rem;
    font-weight: 600;
}

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background-color: #f3f4f6;
    color: #6b7280;
}

.action-btn-group {
    display: flex;
    gap: 0.25rem;
}

.action-btn {
    padding: 0.5rem 0.875rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 15px;
}

.action-btn:hover {
    transform: translateY(-1px);
}

.action-btn-edit {
    background-color: #3b82f6;
    color: white;
}

.action-btn-toggle-active {
    background-color: #10b981;
    color: white;
}

.action-btn-toggle-inactive {
    background-color: #6b7280;
    color: white;
}

.action-btn-delete {
    background-color: #ef4444;
    color: white;
}

.remark-text {
    max-width: 500px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>

<div class="row">
    <div class="col-md-12">
        <!-- Modern Header with Right-Aligned Button -->
        <div class="page-header-gradient">
            <div class="page-header-content">
                <h1><i class="fa fa-comments"></i> <?php echo get_phrase('teacher_remarks'); ?></h1>
                <p><?php echo get_phrase('manage_teacher_remark_templates'); ?></p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-modern btn-modern-light" onclick="showAddModal()">
                    <i class="fa fa-plus"></i> <?php echo get_phrase('add_new_template'); ?>
                </button>
            </div>
        </div>

        <!-- Modern Table Card -->
        <div class="modern-card">
            <!-- Modern Table -->
            <div class="table-responsive">
                <table class="modern-table" id="remarks-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><i class="fa fa-arrows-v"></i></th>
                            <th style="width: 45%;"><?php echo get_phrase('remark_text'); ?></th>
                            <th style="width: 15%;"><?php echo get_phrase('category'); ?></th>
                            <th style="width: 10%;" class="text-center"><?php echo get_phrase('order'); ?></th>
                            <th style="width: 10%;" class="text-center"><?php echo get_phrase('status'); ?></th>
                            <th style="width: 15%;" class="text-center"><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="sortable-tbody">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadTable();
    // Sortable will be initialized after table loads
});

function loadTable() {
    $.ajax({
        url: '<?php echo site_url('teacher_remarks_templates/get_all_ajax'); ?>',
        type: 'GET',
        data: { include_inactive: false },
        dataType: 'json',
        success: function(data) {
            renderTable(data);
            initializeSortable();
        },
        error: function() {
            toastr.error('Failed to load remarks templates');
        }
    });
}

// Make loadTable globally accessible for modal forms
window.loadTable = loadTable;

function renderTable(data) {
    var tbody = $('#sortable-tbody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;"><?php echo get_phrase('no_templates_found'); ?></td></tr>');
        return;
    }
    
    data.forEach(function(item) {
        var categoryClass = 'category-none';
        var categoryText = '<?php echo get_phrase('none'); ?>';
        
        if (item.category) {
            if (item.category === 'positive') {
                categoryClass = 'category-positive';
                categoryText = '<?php echo get_phrase('positive'); ?>';
            } else if (item.category === 'neutral') {
                categoryClass = 'category-neutral';
                categoryText = '<?php echo get_phrase('neutral'); ?>';
            } else if (item.category === 'negative') {
                categoryClass = 'category-negative';
                categoryText = '<?php echo get_phrase('negative'); ?>';
            }
        }
        
        var statusClass = item.is_active == 1 ? 'status-active' : 'status-inactive';
        var statusText = item.is_active == 1 ? '<?php echo get_phrase('active'); ?>' : '<?php echo get_phrase('inactive'); ?>';
        var toggleIcon = item.is_active == 1 ? 'eye-slash' : 'eye';
        var toggleBtnClass = item.is_active == 1 ? 'action-btn-toggle-active' : 'action-btn-toggle-inactive';
        
        var remarkText = item.remark_text;
        if (remarkText.length > 80) {
            remarkText = remarkText.substring(0, 80) + '...';
        }
        
        var row = '<tr data-id="' + item.id + '" style="cursor:move;">' +
            '<td class="text-center drag-handle"><i class="fa fa-arrows-v"></i></td>' +
            '<td><div class="remark-text" title="' + item.remark_text.replace(/"/g, '&quot;') + '">' + remarkText + '</div></td>' +
            '<td><span class="category-badge ' + categoryClass + '">' + categoryText + '</span></td>' +
            '<td class="text-center">' + item.display_order + '</td>' +
            '<td class="text-center"><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>' +
            '<td class="text-center">' +
                '<div class="action-btn-group">' +
                    '<button class="action-btn action-btn-edit" onclick="editTemplate(' + item.id + ')" title="<?php echo get_phrase('edit'); ?>">' +
                        '<i class="fa fa-edit"></i>' +
                    '</button>' +
                    '<button class="action-btn ' + toggleBtnClass + '" data-active="' + item.is_active + '" onclick="toggleActive(this, ' + item.id + ')" title="<?php echo get_phrase('toggle_status'); ?>">' +
                        '<i class="fa fa-' + toggleIcon + '"></i>' +
                    '</button>' +
                    '<button class="action-btn action-btn-delete" onclick="deleteTemplate(' + item.id + ')" title="<?php echo get_phrase('delete'); ?>">' +
                        '<i class="fa fa-trash"></i>' +
                    '</button>' +
                '</div>' +
            '</td>' +
            '</tr>';
        
        tbody.append(row);
    });
}

function initializeSortable() {
    var tbody = document.getElementById('sortable-tbody');
    if (!tbody || tbody.children.length === 0) return;
    
    // Simple drag and drop implementation
    var draggedElement = null;
    var placeholder = null;
    
    Array.from(tbody.children).forEach(function(row) {
        row.draggable = true;
        
        row.addEventListener('dragstart', function(e) {
            draggedElement = this;
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        row.addEventListener('dragend', function(e) {
            this.style.opacity = '';
            draggedElement = null;
            
            // Remove placeholder if exists
            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.removeChild(placeholder);
                placeholder = null;
            }
            
            // Update order
            updateOrderFromDOM();
        });
        
        row.addEventListener('dragover', function(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            
            if (this !== draggedElement) {
                var rect = this.getBoundingClientRect();
                var midpoint = rect.top + (rect.height / 2);
                
                if (e.clientY < midpoint) {
                    this.parentNode.insertBefore(draggedElement, this);
                } else {
                    this.parentNode.insertBefore(draggedElement, this.nextSibling);
                }
            }
            return false;
        });
        
        row.addEventListener('drop', function(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            return false;
        });
    });
}

function updateOrderFromDOM() {
    var orderData = [];
    $('#sortable-tbody tr').each(function(index) {
        var id = $(this).data('id');
        if (id) {
            orderData.push({
                id: id,
                display_order: index + 1
            });
        }
    });
    
    if (orderData.length > 0) {
        updateOrder(orderData);
    }
}

function updateOrder(orderData) {
    // Show processing modal immediately
    showAjaxModal_alert('<?php echo get_phrase('updating_order'); ?>...', 'loading', false, false);
    
    // Small delay to ensure modal renders before AJAX blocks UI
    setTimeout(function() {
        $.ajax({
            url: '<?php echo site_url('teacher_remarks_templates/update_order'); ?>',
            type: 'POST',
            data: { order_data: orderData },
            dataType: 'json',
            success: function(response) {
                // Hide processing modal
                $('#modal_alert').modal('hide');
                
                if (response.success) {
                    toastr.success(response.message || '<?php echo get_phrase('order_updated_successfully'); ?>');
                } else {
                    toastr.error(response.message || '<?php echo get_phrase('failed_to_update_order'); ?>');
                }
            },
            error: function() {
                // Hide processing modal
                $('#modal_alert').modal('hide');
                toastr.error('<?php echo get_phrase('failed_to_update_order'); ?>');
            }
        });
    }, 50);
}

function showAddModal() {
    $('#modal_ajax .modal-dialog').removeClass('modal-lg modal-xl modal-sm');
    $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_ajax').modal('show', {backdrop: 'static'});
    
    $.ajax({
        url: '<?php echo site_url('teacher_remarks_templates/get_form'); ?>',
        type: 'POST',
        success: function(response) {
            $('#modal_ajax .modal-body').html(response);
        },
        error: function() {
            $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Form</h4><p>Unable to load the form.</p></div>');
        }
    });
}

function editTemplate(id) {
    $('#modal_ajax .modal-dialog').removeClass('modal-lg modal-xl modal-sm');
    $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_ajax').modal('show', {backdrop: 'static'});
    
    $.ajax({
        url: '<?php echo site_url('teacher_remarks_templates/get_form'); ?>',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            $('#modal_ajax .modal-body').html(response);
        },
        error: function() {
            $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Form</h4><p>Unable to load the form.</p></div>');
        }
    });
}

function toggleActive(btn, id) {
    var $btn = $(btn);
    var $row = $btn.closest('tr');
    var currentStatus = parseInt($btn.data('active'));
    
    // Show loading modal
    showAjaxModal_alert('<?php echo get_phrase('processing'); ?>...', 'loading', false, false);
    
    $.ajax({
        url: '<?php echo site_url("teacher_remarks_templates/toggle_active/"); ?>' + id,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            // Hide loading modal
            $('#modal_alert').modal('hide');
            
            if (response.success) {
                // Update status badge
                var newStatus = currentStatus == 1 ? 0 : 1;
                var statusBadge = $row.find('.status-badge');
                
                if (newStatus == 1) {
                    statusBadge.removeClass('status-inactive').addClass('status-active').text('<?php echo get_phrase('active'); ?>');
                    $btn.removeClass('action-btn-toggle-inactive').addClass('action-btn-toggle-active');
                    $btn.find('i').removeClass('fa-eye').addClass('fa-eye-slash');
                    $btn.data('active', 1);
                } else {
                    statusBadge.removeClass('status-active').addClass('status-inactive').text('<?php echo get_phrase('inactive'); ?>');
                    $btn.removeClass('action-btn-toggle-active').addClass('action-btn-toggle-inactive');
                    $btn.find('i').removeClass('fa-eye-slash').addClass('fa-eye');
                    $btn.data('active', 0);
                }
                
                toastr.success(response.message || '<?php echo get_phrase('status_updated'); ?>');
            } else {
                toastr.error(response.message || '<?php echo get_phrase('operation_failed'); ?>');
            }
        },
        error: function() {
            // Hide loading modal
            $('#modal_alert').modal('hide');
            toastr.error('<?php echo get_phrase('operation_failed'); ?>');
        }
    });
}

function deleteTemplate(id) {
    if (!confirm('<?php echo get_phrase('confirm_delete'); ?>')) {
        return;
    }
    
    var row = $('tr[data-id="' + id + '"]');
    
    $.ajax({
        url: '<?php echo site_url("teacher_remarks_templates/delete/"); ?>' + id,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                row.fadeOut(400, function() {
                    $(this).remove();
                    toastr.success(response.message || '<?php echo get_phrase('deleted_successfully'); ?>');
                });
            } else {
                toastr.error(response.message || '<?php echo get_phrase('operation_failed'); ?>');
            }
        },
        error: function() {
            toastr.error('<?php echo get_phrase('operation_failed'); ?>');
        }
    });
}
</script>
