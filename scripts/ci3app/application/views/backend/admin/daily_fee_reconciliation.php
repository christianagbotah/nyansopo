<link href="<?php echo base_url(); ?>assets/cdn/css/flatpickr.min.css" rel="stylesheet">
<style>
.recon-header{background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);padding:2.5rem;border-radius:16px;margin-bottom:2rem;box-shadow:0 10px 40px rgba(139,92,246,0.25);position:relative;overflow:hidden}
.recon-header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,0.1),transparent);border-radius:50%}
.recon-header h2{color:#fff;font-size:2rem;font-weight:800;margin:0;letter-spacing:-0.5px}
.recon-header p{color:rgba(255,255,255,0.85);margin:0.75rem 0 0;font-size:1rem}
.filter-bar{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-bottom:1.5rem;border:1px solid rgba(139,92,246,0.08);position:sticky;top:0;z-index:100;transition:all 0.3s}
.filter-bar.scrolled{box-shadow:0 8px 24px rgba(0,0,0,0.12);border-color:rgba(139,92,246,0.15)}
.form-group label{display:block;margin-bottom:0.625rem;font-weight:600;color:#475569;font-size:0.9375rem}
.form-control{border:2px solid #e2e8f0;border-radius:10px;padding:0.875rem 1rem;transition:all 0.3s;background:#f8fafc;height:45px}
.form-control:focus{outline:none;border-color:#8b5cf6;background:#fff;box-shadow:0 0 0 4px rgba(139,92,246,0.1)}
.search-input-wrapper{position:relative}
.search-input-wrapper input{padding-left:2.5rem}
.search-input-wrapper .search-icon{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;z-index:10}
.search-input-wrapper input:focus ~ .search-icon{color:#8b5cf6}
.btn-recon{padding:0.875rem 1.75rem;border:none;border-radius:10px;font-weight:700;cursor:pointer;transition:all 0.3s;display:inline-flex;align-items:center;gap:0.5rem;justify-content:center;height:45px}
.btn-primary{background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;box-shadow:0 4px 12px rgba(139,92,246,0.3)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(139,92,246,0.4)}
.btn-success{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,0.3)}
.btn-success:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(16,185,129,0.4)}
.btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;box-shadow:0 4px 12px rgba(245,158,11,0.3)}
.btn-warning:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(245,158,11,0.4)}
.students-table{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-top:1.5rem;border:1px solid rgba(139,92,246,0.08)}
.students-table h4{font-size:1.125rem;font-weight:700;margin:0 0 1.5rem;color:#0f172a;display:flex;align-items:center;gap:0.5rem}
.students-table h4 i{color:#8b5cf6}
.table{width:100%;border-collapse:separate;border-spacing:0}
.table thead th{background:linear-gradient(135deg,#f8fafc,#fff);padding:1rem;font-weight:700;color:#0f172a;border-bottom:2px solid #e2e8f0;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.5px}
.table tbody tr{transition:all 0.3s}
.table tbody tr:hover{background:#f8fafc}
.table tbody tr.selected-row{background:rgba(139,92,246,0.08);border-left:4px solid #8b5cf6}
.table tbody tr.selected-row:hover{background:rgba(139,92,246,0.12)}
.table tbody td{padding:1rem;border-bottom:1px solid #e2e8f0;color:#475569;vertical-align:middle}
.balance-badge{display:inline-block;padding:0.375rem 0.875rem;border-radius:8px;font-size:0.8125rem;font-weight:600}
.balance-arrears{background:rgba(239,68,68,0.1);color:#dc2626}
.balance-prepaid{background:rgba(16,185,129,0.1);color:#059669}
.balance-zero{background:rgba(100,116,139,0.1);color:#64748b}
.bulk-action-bar{background:linear-gradient(135deg,#fef3c7,#fde68a);padding:1.25rem 1.75rem;border-radius:12px;margin-bottom:1rem;display:none;align-items:center;gap:1rem;border:2px solid #fbbf24;box-shadow:0 4px 16px rgba(251,191,36,0.3);position:sticky;top:0;z-index:90;overflow:hidden}
.bulk-action-bar::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:shimmer 2s infinite}
@keyframes shimmer{0%{left:-100%}100%{left:100%}}
.bulk-action-bar.active{display:flex;animation:slideDown 0.3s ease-out}
@keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.bulk-action-bar.scrolled{box-shadow:0 8px 24px rgba(251,191,36,0.5);border-width:3px}
.bulk-action-bar strong{font-size:1rem;color:#92400e}
.bulk-action-bar .fa-check-circle{color:#10b981;margin-right:0.5rem;animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}
.checkbox-cell{text-align:center;width:50px;padding:1rem 0.5rem!important}
.custom-checkbox{position:relative;display:inline-block;width:22px;height:22px;cursor:pointer}
.custom-checkbox input[type="checkbox"]{position:absolute;opacity:0;cursor:pointer;height:0;width:0}
.checkbox-mark{position:absolute;top:0;left:0;height:22px;width:22px;background:#fff;border:2.5px solid #cbd5e1;border-radius:6px;transition:all 0.3s ease}
.custom-checkbox:hover .checkbox-mark{border-color:#8b5cf6;transform:scale(1.1)}
.custom-checkbox input:checked ~ .checkbox-mark{background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-color:#8b5cf6;transform:scale(1.05)}
.checkbox-mark:after{content:"";position:absolute;display:none;left:6px;top:2px;width:5px;height:10px;border:solid white;border-width:0 2.5px 2.5px 0;transform:rotate(45deg)}
.custom-checkbox input:checked ~ .checkbox-mark:after{display:block}
.custom-checkbox input:checked ~ .checkbox-mark{animation:checkboxPulse 0.4s ease}
@keyframes checkboxPulse{0%{transform:scale(1)}50%{transform:scale(1.15)}100%{transform:scale(1.05)}}
.student-code{font-family:monospace;font-weight:600;color:#8b5cf6}
.no-results{text-align:center;padding:3rem;color:#94a3b8}
.no-results i{font-size:4rem;color:#cbd5e1;margin-bottom:1rem}
.loading-spinner{text-align:center;padding:3rem}
.loading-spinner i{font-size:3rem;color:#8b5cf6;animation:spin 1s linear infinite}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
</style>

<script>
// Collapse sidebar when page loads
$(document).ready(function() {
    if (typeof toggleSidebarMenu === 'function') {
        toggleSidebarMenu();
    } else if ($('body').hasClass('sidebar-narrow')) {
        $('body').removeClass('sidebar-narrow');
    } else {
        $('body').addClass('sidebar-narrow');
    }
    
    // Enhance sticky filter bar with scroll effect
    $(window).on('scroll', function() {
        var scrollTop = $(window).scrollTop();
        
        if (scrollTop > 50) {
            $('.filter-bar').addClass('scrolled');
            $('.bulk-action-bar').addClass('scrolled');
        } else {
            $('.filter-bar').removeClass('scrolled');
            $('.bulk-action-bar').removeClass('scrolled');
        }
    });
});
</script>

<div class="recon-header">
    <h2><i class="fa fa-balance-scale"></i> Daily Fee Reconciliation</h2>
    <p>Adjust student arrears and prepaid balances for fee collection errors</p>
</div>

<div class="filter-bar">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label><i class="fa fa-school"></i> Class</label>
                <select id="filter_class" class="form-control">
                    <option value="">All Classes</option>
                    <?php getFullClassList(); ?>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label><i class="fa fa-user"></i> Student Name</label>
                <div class="search-input-wrapper">
                    <input type="text" id="filter_student_name" class="form-control" placeholder="Search by name...">
                    <i class="fa fa-search search-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label><i class="fa fa-utensils"></i> Fee Item</label>
                <select id="filter_fee_item" class="form-control">
                    <option value="all">All Items</option>
                    <?php if (is_fee_module_enabled('feeding')): ?><option value="feeding">Feeding</option><?php endif; ?>
                    <?php if (is_fee_module_enabled('breakfast')): ?><option value="breakfast">Breakfast</option><?php endif; ?>
                    <?php if (is_fee_module_enabled('classes')): ?><option value="classes">Classes</option><?php endif; ?>
                    <?php if (is_fee_module_enabled('water')): ?><option value="water">Water</option><?php endif; ?>
                    <?php if (is_fee_module_enabled('transport')): ?><option value="transport">Transport</option><?php endif; ?>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label><i class="fa fa-filter"></i> Balance Type</label>
                <select id="filter_balance_type" class="form-control">
                    <option value="all">All Balances</option>
                    <option value="arrears">Arrears Only</option>
                    <option value="prepaid">Prepaid Only</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>&nbsp;</label>
                <button class="btn-recon btn-primary btn-block" onclick="loadStudents()">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </div>
    </div>
    <div class="alert alert-info" style="margin-top:1rem;margin-bottom:0;border-radius:10px">
        <i class="fa fa-info-circle"></i> <strong>Note:</strong> Showing total cumulative balances across all years and terms. Reconciliation adjustments are applied to the current running term.
    </div>
</div>

<div class="bulk-action-bar" id="bulk_action_bar">
    <div style="flex:1">
        <i class="fa fa-check-circle"></i>
        <strong><span id="selected_count">0</span> student(s) selected</strong>
    </div>
    <button class="btn-recon btn-success" onclick="showBulkAdjustmentModal()">
        <i class="fa fa-edit"></i> Bulk Adjust
    </button>
    <button class="btn-recon btn-warning" onclick="clearSelection()">
        <i class="fa fa-times"></i> Clear
    </button>
</div>

<div class="students-table">
    <h4>
        <i class="fa fa-users"></i> Students with Balances
        <span id="table_filter_count" style="float: right; background: #8b5cf6; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; display: none;"></span>
    </h4>
    <div id="students_container">
        <div class="no-results">
            <i class="fa fa-search"></i>
            <p>Use the filters above to search for students with arrears or prepaid balances</p>
        </div>
    </div>
</div>

<script>
const RUNNING_YEAR = '<?php echo $running_year; ?>';
const RUNNING_TERM = <?php echo $running_term; ?>;

// Live filter for student name on main page
$('#filter_student_name').on('keyup', function() {
    var searchTerm = $(this).val().toLowerCase().trim();
    var visibleCount = 0;
    var totalCount = 0;
    
    if ($('#students_container table').length === 0) {
        // No table loaded yet
        return;
    }
    
    if (searchTerm === '') {
        // Show all rows
        $('#students_container tbody tr').show();
        $('#table_filter_count').hide();
    } else {
        // Filter rows
        $('#students_container tbody tr').each(function() {
            totalCount++;
            var studentName = $(this).find('td').eq(2).text().toLowerCase();
            var studentCode = $(this).find('.student-code').text().toLowerCase();
            
            if (studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        // Show filter badge
        $('#table_filter_count').text('Showing ' + visibleCount + ' of ' + totalCount + ' students').show();
    }
    
    // Update bulk selection if any were hidden
    updateBulkActionBar();
});

function loadStudents() {
    $('#students_container').html('<div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i><p>Loading students...</p></div>');
    
    $.ajax({
        url: '<?php echo base_url(); ?>admin/load_reconciliation_students',
        type: 'POST',
        data: {
            class_id: $('#filter_class').val(),
            student_name: $('#filter_student_name').val(),
            fee_item: $('#filter_fee_item').val(),
            balance_type: $('#filter_balance_type').val()
        },
        dataType: 'json',
        success: function(response) {
            if ((response.success || response.status === 'success') && (response.students || response.data) && (response.students?.length > 0 || response.data?.length > 0)) {
                var students = response.students || response.data;
                renderStudentsTable(students);
            } else {
                $('#students_container').html('<div class="no-results"><i class="fa fa-inbox"></i><p>No students found matching the selected criteria</p></div>');
            }
        },
        error: function() {
            $('#students_container').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> Failed to load students</div>');
        }
    });
}

function renderStudentsTable(students) {
    var html = '<table class="table">';
    html += '<thead><tr>';
    html += '<th class="checkbox-cell">';
    html += '<label class="custom-checkbox" title="Select All">';
    html += '<input type="checkbox" id="select_all">';
    html += '<span class="checkbox-mark"></span>';
    html += '</label>';
    html += '</th>';
    html += '<th>Student Code</th>';
    html += '<th>Student Name</th>';
    html += '<th>Class</th>';
    <?php if (is_fee_module_enabled('feeding')): ?>html += '<th>Feeding</th>';<?php endif; ?>
    <?php if (is_fee_module_enabled('breakfast')): ?>html += '<th>Breakfast</th>';<?php endif; ?>
    <?php if (is_fee_module_enabled('classes')): ?>html += '<th>Classes</th>';<?php endif; ?>
    <?php if (is_fee_module_enabled('water')): ?>html += '<th>Water</th>';<?php endif; ?>
    <?php if (is_fee_module_enabled('transport')): ?>html += '<th>Transport</th>';<?php endif; ?>
    html += '<th>Actions</th>';
    html += '</tr></thead><tbody>';
    
    students.forEach(function(student) {
        html += '<tr>';
        html += '<td class="checkbox-cell">';
        html += '<label class="custom-checkbox">';
        html += '<input type="checkbox" class="bulk-select-checkbox" data-student-id="' + student.student_id + '" data-student-name="' + student.student_name + '">';
        html += '<span class="checkbox-mark"></span>';
        html += '</label>';
        html += '</td>';
        html += '<td><span class="student-code">' + student.student_code + '</span></td>';
        html += '<td>' + student.student_name + '</td>';
        html += '<td>' + student.class_name + '</td>';
        <?php if (is_fee_module_enabled('feeding')): ?>html += '<td>' + formatBalance(student.feeding_balance, student.feeding_arrears, 'feeding') + '</td>';<?php endif; ?>
        <?php if (is_fee_module_enabled('breakfast')): ?>html += '<td>' + formatBalance(student.breakfast_balance, student.breakfast_arrears, 'breakfast') + '</td>';<?php endif; ?>
        <?php if (is_fee_module_enabled('classes')): ?>html += '<td>' + formatBalance(student.classes_balance, student.classes_arrears, 'classes') + '</td>';<?php endif; ?>
        <?php if (is_fee_module_enabled('water')): ?>html += '<td>' + formatBalance(student.water_balance, student.water_arrears, 'water') + '</td>';<?php endif; ?>
        <?php if (is_fee_module_enabled('transport')): ?>html += '<td>' + formatBalance(student.transport_balance, student.transport_arrears, 'transport') + '</td>';<?php endif; ?>
        html += '<td>';
        html += '<button class="btn btn-sm btn-success" onclick="showAdjustModal(' + student.student_id + ')" title="Adjust Balance"><i class="fa fa-edit"></i></button> ';
        html += '<button class="btn btn-sm btn-info" onclick="showHistoryModal(' + student.student_id + ')" title="View History"><i class="fa fa-history"></i></button>';
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    $('#students_container').html(html);
    
    // Setup bulk selection with enhanced behavior
    $('#select_all').change(function() {
        var isChecked = $(this).is(':checked');
        $('.bulk-select-checkbox').prop('checked', isChecked).each(function() {
            if (isChecked) {
                $(this).closest('tr').addClass('selected-row');
            } else {
                $(this).closest('tr').removeClass('selected-row');
            }
        });
        updateBulkActionBar();
    });
    
    $('.bulk-select-checkbox').change(function() {
        if ($(this).is(':checked')) {
            $(this).closest('tr').addClass('selected-row');
        } else {
            $(this).closest('tr').removeClass('selected-row');
        }
        
        // Update select all checkbox state
        var totalCheckboxes = $('.bulk-select-checkbox').length;
        var checkedCheckboxes = $('.bulk-select-checkbox:checked').length;
        $('#select_all').prop('checked', totalCheckboxes === checkedCheckboxes);
        
        updateBulkActionBar();
    });
}

function formatBalance(prepaid, arrears, feeType) {
    prepaid = parseFloat(prepaid) || 0;
    arrears = parseFloat(arrears) || 0;
    
    var html = '';
    
    if (arrears > 0 && prepaid > 0) {
        html += '<span class="balance-prepaid"><span class="' + feeType + '-balance-value" data-balance="' + prepaid.toFixed(2) + '">Prepaid: GH₵' + prepaid.toFixed(2) + '</span></span><br>';
        html += '<span class="balance-arrears"><span class="' + feeType + '-arrears-value" data-arrears="' + arrears.toFixed(2) + '">Arrears: GH₵' + arrears.toFixed(2) + '</span></span>';
    } else if (arrears > 0) {
        html += '<span class="balance-arrears"><span class="' + feeType + '-arrears-value" data-arrears="' + arrears.toFixed(2) + '">Arrears: GH₵' + arrears.toFixed(2) + '</span></span>';
        html += '<span class="' + feeType + '-balance-value" data-balance="0" style="display:none;"></span>';
    } else if (prepaid > 0) {
        html += '<span class="balance-prepaid"><span class="' + feeType + '-balance-value" data-balance="' + prepaid.toFixed(2) + '">Prepaid: GH₵' + prepaid.toFixed(2) + '</span></span>';
        html += '<span class="' + feeType + '-arrears-value" data-arrears="0" style="display:none;"></span>';
    } else {
        html += '<span class="balance-zero">GH₵0.00</span>';
        html += '<span class="' + feeType + '-balance-value" data-balance="0" style="display:none;"></span>';
        html += '<span class="' + feeType + '-arrears-value" data-arrears="0" style="display:none;"></span>';
    }
    
    return html;
}

function updateBulkActionBar() {
    var selectedCount = $('.bulk-select-checkbox:checked').length;
    $('#selected_count').text(selectedCount);
    if (selectedCount > 0) {
        $('#bulk_action_bar').addClass('active');
    } else {
        $('#bulk_action_bar').removeClass('active');
    }
}

function clearSelection() {
    $('.bulk-select-checkbox').prop('checked', false);
    $('#select_all').prop('checked', false);
    $('.selected-row').removeClass('selected-row');
    updateBulkActionBar();
}

function showAdjustModal(student_id) {
    showAjaxModal('<?php echo base_url(); ?>modal/daily_fee_reconciliation_adjust/' + student_id, 'xlarge');
}

function showBulkAdjustmentModal() {
    var selectedStudents = [];
    $('.bulk-select-checkbox:checked').each(function() {
        selectedStudents.push($(this).data('student-id'));
    });
    
    if (selectedStudents.length === 0) {
        $.gritter.add({
            title: 'No Selection',
            text: 'Please select at least one student',
            class_name: 'gritter-error'
        });
        return;
    }
    
    // Load the modal
    $('#bulkReconciliationModal').remove();
    
    $.ajax({
        url: '<?php echo base_url(); ?>modal/daily_fee_bulk_reconciliation',
        type: 'GET',
        success: function(response) {
            var modalHtml = '<div class="modal fade" id="bulkReconciliationModal" tabindex="-1" role="dialog">' +
                           '<div class="modal-dialog modal-lg" role="document" style="max-width: 98%; width: 1400px;">' +
                           '<div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">' +
                           response +
                           '</div></div></div>';
            $('body').append(modalHtml);
            $('#bulkReconciliationModal').modal('show');
            
            // After modal loads, populate with selected students
            setTimeout(function() {
                if (typeof loadBulkStudentsData === 'function') {
                    loadBulkStudentsData(selectedStudents);
                }
            }, 300);
        },
        error: function() {
            showConfirmModal('Error', 'Failed to load bulk reconciliation modal', null, 'OK', 'danger');
        }
    });
}

function showHistoryModal(student_id) {
    showAjaxModal('<?php echo base_url(); ?>modal/daily_fee_reconciliation_history/' + student_id);
}
</script>

<script>
function showBulkReconciliationModal() {
    $('#bulkReconciliationModal').remove();
    
    $.ajax({
        url: '<?php echo base_url(); ?>modal/daily_fee_bulk_reconciliation',
        type: 'GET',
        success: function(response) {
            var modalHtml = '<div class="modal fade" id="bulkReconciliationModal" tabindex="-1" role="dialog">' +
                           '<div class="modal-dialog modal-lg" role="document" style="max-width: 900px;">' +
                           '<div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">' +
                           response +
                           '</div></div></div>';
            $('body').append(modalHtml);
            $('#bulkReconciliationModal').modal('show');
        },
        error: function() {
            showConfirmModal('Error', 'Failed to load bulk reconciliation modal', null, 'OK', 'danger');
        }
    });
}
</script>
