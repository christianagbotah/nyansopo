<?php 
/**
 * Modern Bad Debt Modal
 * Professional, contemporary UI/UX design
 * Shows muted students (mute = 1) with unpaid invoices for the current term
 * 
 * Parameters:
 * $param2 = term
 * $param3 = year
 */

// Currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

// Get year and term from parameters
$year = $param3;
$term = $param2;

// Get muted students with unpaid invoices
$query = "
    SELECT DISTINCT 
        i.student_id,
        s.name as student_name,
        e.class_id,
        SUM(i.due) as total_due
    FROM invoice i
    INNER JOIN enroll e ON i.student_id = e.student_id 
        AND i.year = e.year 
        AND i.term = e.term
    INNER JOIN student s ON i.student_id = s.student_id
    WHERE i.status = 'unpaid' 
        AND e.mute = '1'
        AND i.year = ?
        AND i.term = ?
    GROUP BY i.student_id, s.name, e.class_id
    ORDER BY total_due DESC, s.name ASC
";

$students_with_debt = $this->db->query($query, [$year, $term])->result();

$invoice_code_f = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
$inv_number_len = strlen($invoice_code_f);
?>

<!-- Load Modern CSS -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/modern-bad-debt-modal.css">

<div class="bad-debt-modal-container">
    <!-- Modern Header -->
    <div class="bad-debt-header">
        <h3>
            <i class="entypo-attention"></i>
            Bad Debt Report
        </h3>
        <p>Muted students with unpaid invoices for Year: <?php echo $year; ?>, Term: <?php echo $term; ?></p>
        <p style="font-size: 12px; margin-top: 5px;">
            <i class="entypo-info"></i> These students have been marked as inactive/muted but still have outstanding balances.
        </p>
    </div>

    <?php if (!empty($students_with_debt)): ?>
    <!-- Bulk Action Bar -->
    <div class="bulk-action-bar">
        <div class="btn-group">
            <button type="button" class="btn-modern btn-modern-success" onclick="bulkUnmute()" id="bulk_unmute_btn" disabled>
                <i class="entypo-check"></i>
                <span>Unmute Selected</span>
                <span class="badge-count" id="selected_count">0</span>
            </button>
            <button type="button" class="btn-modern btn-modern-danger" onclick="bulkWriteOff()" id="bulk_writeoff_btn" disabled>
                <i class="entypo-cancel"></i>
                <span>Write Off Selected</span>
                <span class="badge-count" id="selected_count_writeoff">0</span>
            </button>
        </div>
        <button type="button" class="btn-modern btn-modern-secondary" onclick="toggleSelectAll()" id="select_all_btn">
            <i class="entypo-check"></i>
            <span>Select All</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Modern Table Container -->
    <div class="modern-table-container">
        <table class="modern-table" id="bad_debt_table">
            <thead>
                <tr>
                    <?php if (!empty($students_with_debt)): ?>
                    <th style="width: 50px;">
                        <label class="custom-checkbox-container">
                            <input type="checkbox" id="select_all_checkbox" onchange="toggleSelectAll()">
                            <span class="custom-checkbox"></span>
                        </label>
                    </th>
                    <?php endif; ?>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th style="text-align: right;">Amount Owed</th>
                    <th>Invoices</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>   
            </thead>
            <tbody>
                <?php if (empty($students_with_debt)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="entypo-check"></i>
                                <h4>No Bad Debt Found!</h4>
                                <p>All muted students have cleared their balances.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($students_with_debt as $student): 
                        $student_id = $student->student_id;
                        $student_name = $student->student_name;
                        $class_id = $student->class_id;
                        $total_due = $student->total_due;
                        
                        // Get class name
                        $class_name = getFullClassName($class_id);
                        
                        // Get all unpaid invoices for this student
                        $this->db->select('invoice_code, amount, amount_paid, due, creation_timestamp');
                        $this->db->where('student_id', $student_id);
                        $this->db->where('year', $year);
                        $this->db->where('term', $term);
                        $this->db->where('status', 'unpaid');
                        $this->db->where('mute', '1');
                        $this->db->where('can_delete !=', 'trash');
                        $this->db->group_by('invoice_code');
                        $unpaid_invoices = $this->db->get('invoice')->result();
                        
                        $invoice_count = count($unpaid_invoices);
                    ?>
                        <tr>
                            <td style="text-align: center;">
                                <label class="custom-checkbox-container">
                                    <input type="checkbox" class="student_checkbox" 
                                           data-student-id="<?php echo $student_id; ?>" 
                                           data-class-id="<?php echo $class_id; ?>"
                                           data-student-name="<?php echo htmlspecialchars($student_name); ?>"
                                           onchange="updateBulkButtons()">
                                    <span class="custom-checkbox"></span>
                                </label>
                            </td>
                            <td>
                                <strong style="font-size: 13px; color: #2d3748;"><?php echo $student_name; ?></strong>
                            </td>
                            <td>
                                <span style="color: #4a5568; font-weight: 500; font-size: 12px;"><?php echo $class_name; ?></span>
                            </td>
                            <td style="text-align: right;">
                                <span class="amount-display">
                                    <?php echo numfmt_format_currency($fmt, $total_due, $currency); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-modern badge-modern-danger">
                                    <i class="entypo-doc-text"></i>
                                    <?php echo $invoice_count; ?>
                                </span>
                                <br>
                                <small style="color: #718096; margin-top: 4px; display: block; font-size: 10px;">
                                    <?php 
                                    $invoice_codes = array();
                                    foreach($unpaid_invoices as $inv) {
                                        $invoice_codes[] = $inv->invoice_code;
                                    }
                                    echo implode(', ', array_slice($invoice_codes, 0, 2));
                                    if (count($invoice_codes) > 2) {
                                        echo '...';
                                    }
                                    ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge-modern badge-modern-warning">
                                    <i class="entypo-block"></i>
                                    MUTED
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="dropup">
                                    <button class="inline-flex items-center p-2.5 text-xl font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg" type="button" data-toggle="dropdown">
                                        <i class="entypo-dot-3"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" style="font-size: 13px; min-width: 160px; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); border: none; padding: 6px;">
                                        <li>
                                            <a href="#" onclick="view_student_invoices(<?php echo $student_id; ?>); return false;" style="color: #4299e1; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                <i class="entypo-credit-card" style="font-size: 14px;"></i>
                                                <span>View Invoices</span>
                                            </a>
                                        </li>
                                        <li class="divider" style="margin: 4px 0;"></li>
                                        <li>
                                            <a href="#" onclick="unmute_student(<?php echo $student_id; ?>, <?php echo $class_id; ?>); return false;" style="color: #48bb78; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                <i class="entypo-check" style="font-size: 14px;"></i>
                                                <span>Unmute</span>
                                            </a>
                                        </li>
                                        <li class="divider" style="margin: 4px 0;"></li>
                                        <li>
                                            <a href="#" onclick="write_off_debt(<?php echo $student_id; ?>); return false;" style="color: #f56565; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                <i class="entypo-cancel" style="font-size: 14px;"></i>
                                                <span>Write Off</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($students_with_debt)): ?>
            <tfoot>
                <tr>
                    <td></td>
                    <td colspan="2" style="text-align: right; font-size: 13px;">
                        <strong>TOTAL BAD DEBT:</strong>
                    </td>
                    <td style="text-align: right;">
                        <span class="total-amount-display">
                            <?php 
                            $grand_total = 0;
                            foreach($students_with_debt as $student) {
                                $grand_total += $student->total_due;
                            }
                            echo numfmt_format_currency($fmt, $grand_total, $currency); 
                            ?>
                        </span>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php if (!empty($students_with_debt)): ?>
    <!-- Export Buttons -->
    <div class="export-buttons-container">
        <button class="btn-export btn-export-excel" id="export_excel_btn">
            <i class="entypo-download"></i>
            Export to Excel
        </button>
        <button class="btn-export btn-export-pdf" id="export_pdf_btn">
            <i class="entypo-doc-text"></i>
            Export to PDF
        </button>
        <button class="btn-export btn-export-print" id="export_print_btn">
            <i class="entypo-print"></i>
            Print Report
        </button>
    </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Initialize DataTables with modern configuration
        var table = $('#bad_debt_table').DataTable({
            dom: 'lfrtip', // Remove default buttons
            order: [[3, 'desc']], // Sort by amount descending
            pageLength: 25,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search students...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ students",
                infoEmpty: "No students found",
                infoFiltered: "(filtered from _TOTAL_ total students)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next →",
                    previous: "← Previous"
                }
            },
            drawCallback: function() {
                // Re-apply custom checkbox styling after table redraw
                updateBulkButtons();
            }
        });

        // Custom export button handlers
        $('#export_excel_btn').on('click', function() {
            table.button('.buttons-excel').trigger();
        });

        $('#export_pdf_btn').on('click', function() {
            table.button('.buttons-pdf').trigger();
        });

        $('#export_print_btn').on('click', function() {
            table.button('.buttons-print').trigger();
        });

        // Add DataTables buttons (hidden, triggered by custom buttons)
        new $.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    extend: 'excel',
                    text: 'Export to Excel',
                    className: 'hidden',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    },
                    title: 'Bad Debt Report - Year <?php echo $year; ?> Term <?php echo $term; ?>'
                },
                {
                    extend: 'pdf',
                    text: 'Export to PDF',
                    className: 'hidden',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    },
                    title: 'Bad Debt Report - Year <?php echo $year; ?> Term <?php echo $term; ?>'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'hidden',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    },
                    title: 'Bad Debt Report - Year <?php echo $year; ?> Term <?php echo $term; ?>'
                }
            ]
        });

        table.buttons().container().appendTo($('#bad_debt_table_wrapper'));
    });

    function updateBulkButtons() {
        var checkedBoxes = $('.student_checkbox:checked');
        var count = checkedBoxes.length;
        
        $('#selected_count').text(count);
        $('#selected_count_writeoff').text(count);
        
        if (count > 0) {
            $('#bulk_unmute_btn').prop('disabled', false);
            $('#bulk_writeoff_btn').prop('disabled', false);
        } else {
            $('#bulk_unmute_btn').prop('disabled', true);
            $('#bulk_writeoff_btn').prop('disabled', true);
        }
        
        // Update select all checkbox state
        var totalBoxes = $('.student_checkbox').length;
        $('#select_all_checkbox').prop('checked', count === totalBoxes && count > 0);
    }

    function toggleSelectAll() {
        var selectAllCheckbox = $('#select_all_checkbox');
        var isChecked = selectAllCheckbox.is(':checked');
        
        $('.student_checkbox').prop('checked', isChecked);
        updateBulkButtons();
    }

    function getSelectedStudents() {
        var students = [];
        $('.student_checkbox:checked').each(function() {
            students.push({
                student_id: $(this).data('student-id'),
                class_id: $(this).data('class-id'),
                student_name: $(this).data('student-name')
            });
        });
        return students;
    }

    function bulkUnmute() {
        var students = getSelectedStudents();
        
        if (students.length === 0) {
            showAjaxModal_alert('Please select at least one student.', 'Warning');
            return;
        }
        
        var studentNames = students.map(function(s) { return s.student_name; }).join(', ');
        var message = 'Are you sure you want to unmute ' + students.length + ' student(s)? This will move them back to active status.\n\nStudents: ' + studentNames;
        
        showConfirmModal(
            'Bulk Unmute Students',
            message,
            function() {
                // Show loading overlay
                showLoadingOverlay();
                
                var completed = 0;
                var failed = 0;
                var total = students.length;
                
                // Process each student
                students.forEach(function(student, index) {
                    $.ajax({
                        url: '<?php echo site_url('admin/student/unmute/'); ?>' + student.student_id + '/' + student.class_id,
                        type: 'POST',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                completed++;
                            } else {
                                failed++;
                            }
                            
                            // Check if all requests completed
                            if (completed + failed === total) {
                                hideLoadingOverlay();
                                var message = 'Bulk unmute completed!\n\n';
                                message += 'Successfully unmuted: ' + completed + ' student(s)\n';
                                if (failed > 0) {
                                    message += 'Failed: ' + failed + ' student(s)';
                                }
                                
                                showAjaxModal_alert(message, 'Success');
                                setTimeout(function() {
                                    location.reload();
                                }, 800);
                            }
                        },
                        error: function() {
                            failed++;
                            
                            if (completed + failed === total) {
                                hideLoadingOverlay();
                                var message = 'Bulk unmute completed with errors!\n\n';
                                message += 'Successfully unmuted: ' + completed + ' student(s)\n';
                                message += 'Failed: ' + failed + ' student(s)';
                                
                                showAjaxModal_alert(message, 'Warning');
                                setTimeout(function() {
                                    location.reload();
                                }, 800);
                            }
                        }
                    });
                });
            },
            'Yes, Unmute All',
            'success'
        );
    }

    function bulkWriteOff() {
        var students = getSelectedStudents();
        
        if (students.length === 0) {
            showAjaxModal_alert('Please select at least one student.', 'Warning');
            return;
        }
        
        var studentNames = students.map(function(s) { return s.student_name; }).join(', ');
        var message = 'Are you sure you want to write off debt for ' + students.length + ' student(s)? This action cannot be undone and will mark all unpaid invoices as paid with a "Write-Off" note.\n\nStudents: ' + studentNames;
        
        showConfirmModal(
            'Bulk Write Off Debt',
            message,
            function() {
                // Show loading overlay
                showLoadingOverlay();
                
                var completed = 0;
                var failed = 0;
                var total = students.length;
                var totalAmount = 0;
                
                // Process each student
                students.forEach(function(student, index) {
                    $.ajax({
                        url: '<?php echo site_url('admin/student/write_off_debt/'); ?>' + student.student_id,
                        type: 'POST',
                        data: {
                            year: '<?php echo $year; ?>',
                            term: '<?php echo $term; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                completed++;
                                if (response.data && response.data.total_written_off) {
                                    totalAmount += parseFloat(response.data.total_written_off);
                                }
                            } else {
                                failed++;
                            }
                            
                            // Check if all requests completed
                            if (completed + failed === total) {
                                hideLoadingOverlay();
                                var message = 'Bulk write-off completed!\n\n';
                                message += 'Successfully wrote off: ' + completed + ' student(s)\n';
                                if (failed > 0) {
                                    message += 'Failed: ' + failed + ' student(s)\n';
                                }
                                if (totalAmount > 0) {
                                    message += '\nTotal amount written off: GH₵ ' + totalAmount.toFixed(2);
                                }
                                
                                showAjaxModal_alert(message, 'Success');
                                setTimeout(function() {
                                    location.reload();
                                }, 800);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Write-off error for student ' + student.student_id + ':', error);
                            failed++;
                            
                            if (completed + failed === total) {
                                hideLoadingOverlay();
                                var message = 'Bulk write-off completed with errors!\n\n';
                                message += 'Successfully wrote off: ' + completed + ' student(s)\n';
                                message += 'Failed: ' + failed + ' student(s)';
                                
                                showAjaxModal_alert(message, 'Warning');
                                setTimeout(function() {
                                    location.reload();
                                }, 800);
                            }
                        }
                    });
                });
            },
            'Yes, Write Off All',
            'danger'
        );
    }

    function view_student_invoices(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/');?>' + student_id, 'take_payment');
    }

    function unmute_student(student_id, class_id) {
        showConfirmModal(
            'Unmute Student',
            'Are you sure you want to unmute this student? This will move them back to active status.',
            function() {
                showLoadingOverlay();
                $.ajax({
                    url: '<?php echo site_url('admin/student/unmute/'); ?>' + student_id + '/' + class_id,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        hideLoadingOverlay();
                        if (response.status === 'success') {
                            showAjaxModal_alert(response.message || 'Student has been unmuted successfully.', 'Success');
                            setTimeout(function() {
                                location.reload();
                            }, 800);
                        } else {
                            showAjaxModal_alert(response.message || 'Error unmuting student.', 'Error');
                        }
                    },
                    error: function() {
                        hideLoadingOverlay();
                        showAjaxModal_alert('Error unmuting student. Please try again.', 'Error');
                    }
                });
            },
            'Yes, Unmute',
            'success'
        );
    }

    function write_off_debt(student_id) {
        showConfirmModal(
            'Write Off Debt',
            'Are you sure you want to write off this debt? This action cannot be undone and will mark all unpaid invoices as paid with a "Write-Off" note.',
            function() {
                showLoadingOverlay();
                $.ajax({
                    url: '<?php echo site_url('admin/student/write_off_debt/'); ?>' + student_id,
                    type: 'POST',
                    data: {
                        year: '<?php echo $year; ?>',
                        term: '<?php echo $term; ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        hideLoadingOverlay();
                        if (response.status === 'success') {
                            showAjaxModal_alert(response.message || 'Debt has been written off successfully.', 'Success');
                            setTimeout(function() {
                                location.reload();
                            }, 800);
                        } else {
                            showAjaxModal_alert(response.message || 'Error writing off debt.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        hideLoadingOverlay();
                        console.error('Write-off error:', error);
                        showAjaxModal_alert('Error writing off debt. Please try again.', 'Error');
                    }
                });
            },
            'Yes, Write Off',
            'danger'
        );
    }

    // Loading overlay functions
    function showLoadingOverlay() {
        if ($('#loading_overlay').length === 0) {
            $('body').append('<div id="loading_overlay" class="loading-overlay"><div class="loading-spinner"></div></div>');
        }
    }

    function hideLoadingOverlay() {
        $('#loading_overlay').remove();
    }
</script>
