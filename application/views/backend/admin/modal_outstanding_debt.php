<?php 
/**
 * Modern Outstanding Debt Modal
 * Professional, contemporary UI/UX design
 * Shows active students (mute = 0) with unpaid invoices for the current term
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

// Get active students with unpaid invoices
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
        AND e.mute = '0'
        AND i.year = ?
        AND i.term = ?
    GROUP BY i.student_id, s.name, e.class_id
    ORDER BY total_due DESC, s.name ASC
";

$students_with_debt = $this->db->query($query, [$year, $term])->result();

$invoice_code_f = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
$inv_number_len = strlen($invoice_code_f);
?>

<!-- Load Modern CSS (shared with Bad Debt Modal) -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/modern-bad-debt-modal.css">

<div class="debt-modal-container outstanding-debt-theme">
    <!-- Modern Header -->
    <div class="debt-header">
        <h3>
            <i class="entypo-credit-card"></i>
            Outstanding Debt Report
        </h3>
        <p>Active students with unpaid invoices for Year: <?php echo $year; ?>, Term: <?php echo $term; ?></p>
        <p style="font-size: 12px; margin-top: 5px;">
            <i class="entypo-info"></i> These students are currently active but have outstanding balances that need to be collected.
        </p>
    </div>

    <?php if (!empty($students_with_debt)): ?>
    <!-- Bulk Action Bar -->
    <div class="bulk-action-bar">
        <div class="btn-group">
            <button type="button" class="btn-modern btn-modern-primary" onclick="bulkTakePayment()" id="bulk_payment_btn" disabled>
                <i class="entypo-credit-card"></i>
                <span>Take Payment</span>
                <span class="badge-count" id="selected_count">0</span>
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
        <table class="modern-table" id="outstanding_debt_table">
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
                                <h4>No Outstanding Debt Found!</h4>
                                <p>All active students have paid their bills.</p>
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
                        $this->db->where('mute', '0');
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
                                <span class="badge-modern badge-modern-info">
                                    <i class="entypo-user"></i>
                                    ACTIVE
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="dropup">
                                    <button class="inline-flex items-center p-2.5 text-xl font-medium text-white bg-gradient-to-r from-blue-600 to-cyan-600 rounded-full hover:from-blue-700 hover:to-cyan-700 transition-all duration-200 shadow-md hover:shadow-lg" type="button" data-toggle="dropdown">
                                        <i class="entypo-dot-3"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" style="font-size: 13px; min-width: 160px; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); border: none; padding: 6px;">
                                        <li>
                                            <a href="#" onclick="invoice_pay_modal(<?php echo $student_id; ?>); return false;" style="color: #9f7aea; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                <i class="entypo-credit-card" style="font-size: 14px;"></i>
                                                <span>Take Payment</span>
                                            </a>
                                        </li>
                                        <li class="divider" style="margin: 4px 0;"></li>
                                        <li>
                                            <a href="#" onclick="view_student_invoices(<?php echo $student_id; ?>); return false;" style="color: #4299e1; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                <i class="entypo-doc-text" style="font-size: 14px;"></i>
                                                <span>View Invoices</span>
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
                        <strong>TOTAL OUTSTANDING DEBT:</strong>
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
        var table = $('#outstanding_debt_table').DataTable({
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
                    title: 'Outstanding Debt Report - Year <?php echo $year; ?> Term <?php echo $term; ?>'
                },
                {
                    extend: 'pdf',
                    text: 'Export to PDF',
                    className: 'hidden',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    },
                    title: 'Outstanding Debt Report - Year <?php echo $year; ?> Term <?php echo $term; ?>'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'hidden',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    },
                    title: 'Outstanding Debt Report - Year <?php echo $year; ?> Term <?php echo $term; ?>'
                }
            ]
        });

        table.buttons().container().appendTo($('#outstanding_debt_table_wrapper'));
    });

    function updateBulkButtons() {
        var checkedBoxes = $('.student_checkbox:checked');
        var count = checkedBoxes.length;
        
        $('#selected_count').text(count);
        
        if (count > 0) {
            $('#bulk_payment_btn').prop('disabled', false);
        } else {
            $('#bulk_payment_btn').prop('disabled', true);
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

    function bulkTakePayment() {
        var students = getSelectedStudents();
        
        if (students.length === 0) {
            showAjaxModal_alert('Please select at least one student.', 'Warning');
            return;
        }
        
        var studentNames = students.map(function(s) { return s.student_name; }).join(', ');
        var message = 'You have selected ' + students.length + ' student(s) for payment collection.\n\nStudents: ' + studentNames + '\n\nNote: You will be taken to the payment portal for each student sequentially.';
        
        showConfirmModal(
            'Bulk Payment Collection',
            message,
            function() {
                // Process students sequentially
                processBulkPayments(students, 0);
            },
            'Proceed',
            'primary'
        );
    }

    function processBulkPayments(students, index) {
        if (index >= students.length) {
            // All done
            showAjaxModal_alert('Payment collection completed for all selected students!', 'Success');
            setTimeout(function() {
                location.reload();
            }, 800);
            return;
        }
        
        var student = students[index];
        var remaining = students.length - index;
        
        // Show payment modal for current student
        showAjaxModal(
            '<?php echo site_url('modal/popup/modal_take_payment/');?>' + student.student_id, 
            'take_payment',
            function() {
                // After payment modal closes, move to next student
                if (remaining > 1) {
                    showConfirmModal(
                        'Continue to Next Student?',
                        'Payment processed for ' + student.student_name + '.\n\n' + (remaining - 1) + ' student(s) remaining.\n\nContinue to next student?',
                        function() {
                            processBulkPayments(students, index + 1);
                        },
                        'Continue',
                        'primary'
                    );
                } else {
                    // Last student
                    showAjaxModal_alert('Payment collection completed for all selected students!', 'Success');
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                }
            }
        );
    }

    function invoice_pay_modal(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id, 'take_payment');
    }

    function view_student_invoices(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/');?>' + student_id, 'take_payment');
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
