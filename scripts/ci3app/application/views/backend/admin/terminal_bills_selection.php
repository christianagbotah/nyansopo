<div class="row" style="margin-top: 25px; max-width: 100%; overflow-x: hidden;">
    <div class="col-md-12" style="max-width: 100%; overflow-x: hidden;">
        <div class="panel panel-primary" style="border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: none; max-width: 100%; overflow-x: hidden;">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px 10px 0 0; padding: 20px;">
                <div class="panel-title" style="font-size: 18px; font-weight: 600; color: white; word-wrap: break-word;">
                    <i class="fa fa-file-invoice" style="margin-right: 8px;"></i> Terminal Bills Report
                </div>
            </div>
            <div class="panel-body" style="padding: 30px; max-width: 100%; overflow-x: hidden;">
                
                <!-- Instructions -->
                <div style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); padding: 16px 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #2196f3; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: flex-start;">
                        <i class="fa fa-info-circle" style="color: #1976d2; font-size: 20px; margin-right: 12px; margin-top: 2px;"></i>
                        <div>
                            <strong style="color: #1565c0; font-size: 14px; display: block; margin-bottom: 6px;">Instructions:</strong>
                            <p style="margin: 0; color: #424242; font-size: 13px; line-height: 1.5;">
                                Select a class, then choose students (single, multiple, or all). The report will generate terminal bills showing arrears and next term bills for each selected student.
                            </p>
                        </div>
                    </div>
                </div>

                <?php echo form_open('admin/terminal_bills_report', array('id' => 'terminal_bills_form', 'target' => '_blank')); ?>
                    
                    <!-- Filters Section - All in One Row -->
                    <div class="filters-container" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e9ecef; max-width: 100%; overflow-x: hidden;">
                        <div class="row" style="max-width: 100%; margin: 0;">
                            <!-- Class Selection -->
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="font-weight: 600; color: #374151; font-size: 13px; display: block; margin-bottom: 8px; word-wrap: break-word;">
                                        <i class="fa fa-school" style="margin-right: 5px;"></i> Select Class <span style="color: #e74c3c;">*</span>
                                    </label>
                                    <select name="class_id" id="class_id" class="form-control select2" required style="border: 2px solid #dee2e6; border-radius: 6px; width: 100%; height: 42px; max-width: 100%;">
                                        <option value="">-- Select Class --</option>
                                        <?php getFullClassList(); ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Next Term -->
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="font-weight: 600; color: #374151; font-size: 13px; display: block; margin-bottom: 8px; word-wrap: break-word;">
                                        <i class="fa fa-calendar" style="margin-right: 5px;"></i> Next Term <span style="color: #e74c3c;">*</span>
                                    </label>
                                    <select name="next_term" id="next_term" class="form-control" required style="border: 2px solid #dee2e6; border-radius: 6px; padding: 10px; height: 42px; width: 100%; max-width: 100%;">
                                        <option value="1">Term 1</option>
                                        <option value="2">Term 2</option>
                                        <option value="3">Term 3</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Next Year -->
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="font-weight: 600; color: #374151; font-size: 13px; display: block; margin-bottom: 8px; word-wrap: break-word;">
                                        <i class="fa fa-calendar-alt" style="margin-right: 5px;"></i> Next Year <span style="color: #e74c3c;">*</span>
                                    </label>
                                    <select name="next_year" id="next_year" class="form-control" required style="border: 2px solid #dee2e6; border-radius: 6px; padding: 10px; height: 42px; width: 100%; max-width: 100%;">
                                        <?php
                                        // Get running year and calculate the next academic year
                                        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
                                        
                                        // Parse the running year (format: YYYY-YYYY)
                                        $year_parts = explode('-', $running_year);
                                        if(count($year_parts) == 2) {
                                            $start_year = intval($year_parts[0]);
                                            $end_year = intval($year_parts[1]);
                                            
                                            // Generate current and next academic year options (limit to one level above) - DESCENDING ORDER
                                            $current_year = $start_year . '-' . $end_year;
                                            $next_year_option = ($start_year + 1) . '-' . ($end_year + 1);
                                        ?>
                                        <option value="<?php echo $next_year_option; ?>" selected><?php echo $next_year_option; ?></option>
                                        <option value="<?php echo $current_year; ?>"><?php echo $current_year; ?></option>
                                        <?php
                                        } else {
                                            // Fallback: just show the running year if format is unexpected
                                        ?>
                                        <option value="<?php echo $running_year; ?>" selected><?php echo $running_year; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Fee Category -->
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="font-weight: 600; color: #374151; font-size: 13px; display: block; margin-bottom: 8px; word-wrap: break-word;">
                                        <i class="fa fa-filter" style="margin-right: 5px;"></i> Fee Category <span style="color: #e74c3c;">*</span>
                                    </label>
                                    <select name="fee_category" id="fee_category" class="form-control" required style="border: 2px solid #dee2e6; border-radius: 6px; padding: 10px; height: 42px; width: 100%; max-width: 100%;">
                                        <option value="all" selected>All</option>
                                        <option value="billed_invoice">Billed Invoice Only</option>
                                        <option value="daily_fees">Daily Fees Only</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Orientation -->
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="font-weight: 600; color: #374151; font-size: 13px; display: block; margin-bottom: 8px; word-wrap: break-word;">
                                        <i class="fa fa-file" style="margin-right: 5px;"></i> Paper Orientation <span style="color: #e74c3c;">*</span>
                                    </label>
                                    <select name="orientation" id="orientation" class="form-control" required style="border: 2px solid #dee2e6; border-radius: 6px; padding: 10px; height: 42px; width: 100%; max-width: 100%;">
                                        <option value="portrait" selected>Portrait (A5)</option>
                                        <option value="landscape">Landscape (A5)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Selection Section -->
                    <div id="student_selection_section" style="display: none; max-width: 100%; overflow-x: hidden;">
                        <div style="background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.04); max-width: 100%; overflow-x: hidden;">
                            <!-- Header with Action Buttons -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                                <div style="font-weight: 600; color: #374151; font-size: 15px; word-wrap: break-word;">
                                    <i class="fa fa-users" style="color: #667eea; margin-right: 8px;"></i> Select Students
                                </div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button type="button" id="select_all_btn" class="btn btn-sm" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3); transition: all 0.2s; white-space: nowrap;">
                                        <i class="fa fa-check-double" style="margin-right: 5px;"></i> Select All
                                    </button>
                                    <button type="button" id="deselect_all_btn" class="btn btn-sm" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3); transition: all 0.2s; white-space: nowrap;">
                                        <i class="fa fa-times" style="margin-right: 5px;"></i> Deselect All
                                    </button>
                                    <button type="submit" class="btn btn-sm" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 8px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4); transition: all 0.2s; white-space: nowrap;">
                                        <i class="fa fa-file-pdf" style="margin-right: 5px;"></i> Generate Report
                                    </button>
                                </div>
                            </div>

                            <!-- Search Box -->
                            <div class="form-group" style="margin-bottom: 20px;">
                                <div style="position: relative;">
                                    <i class="fa fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px;"></i>
                                    <input type="text" id="student_search" class="form-control" placeholder="Search students by name or ID..." style="border: 2px solid #dee2e6; border-radius: 6px; padding: 12px 15px 12px 40px; height: 44px; font-size: 14px; transition: all 0.2s; width: 100%; max-width: 100%;">
                                </div>
                            </div>

                            <!-- Students List -->
                            <div id="students_list" style="max-height: 450px; overflow-y: auto; overflow-x: hidden; border: 2px solid #dee2e6; border-radius: 8px; padding: 12px; background: #fafbfc; width: 100%; max-width: 100%;">
                                <div style="text-align: center; padding: 30px 20px; color: #9ca3af;">
                                    <i class="fa fa-arrow-up" style="font-size: 32px; color: #d1d5db;"></i>
                                    <p style="margin-top: 12px; font-size: 14px; font-weight: 500;">Select a class to load students</p>
                                </div>
                            </div>

                            <!-- Pagination Controls -->
                            <div id="pagination_controls" style="display: none; margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e9ecef;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                    <button type="button" id="prev_page_btn" class="btn btn-sm" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; transition: all 0.2s; white-space: nowrap;" disabled>
                                        <i class="fa fa-chevron-left" style="margin-right: 5px;"></i> Previous
                                    </button>
                                    
                                    <div style="font-weight: 600; color: #374151; font-size: 14px; text-align: center;">
                                        Page <span id="current_page">1</span> of <span id="total_pages">1</span>
                                        <span style="color: #6b7280; font-size: 12px; display: block; margin-top: 2px;">
                                            (Showing <span id="showing_count">0</span> of <span id="total_count">0</span> students)
                                        </span>
                                    </div>
                                    
                                    <button type="button" id="next_page_btn" class="btn btn-sm" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; transition: all 0.2s; white-space: nowrap;">
                                        Next <i class="fa fa-chevron-right" style="margin-left: 5px;"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Selected Count Badge -->
                            <div style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 6px; text-align: center; font-weight: 600; color: #92400e; font-size: 14px; border: 1px solid #fde047; word-wrap: break-word;">
                                <i class="fa fa-check-circle" style="margin-right: 6px;"></i> Selected: <span id="selected_count" style="font-size: 16px; color: #78350f;">0</span> student(s)
                            </div>
                        </div>
                    </div>

                <?php echo form_close(); ?>

            </div>
        </div>
    </div>
</div>

<style>
/* Global Container Overflow Fix */
body {
    overflow-x: hidden;
    max-width: 100%;
}

.row {
    max-width: 100%;
    margin-left: 0;
    margin-right: 0;
}

[class*="col-"] {
    padding-left: 15px;
    padding-right: 15px;
}

/* Filters Container Responsive */
@media (max-width: 991px) {
    .filters-container .col-lg-3 {
        margin-bottom: 10px;
    }
}

@media (max-width: 767px) {
    .filters-container {
        padding: 15px !important;
    }
    
    #student_selection_section > div {
        padding: 15px !important;
    }
}

/* Search Input Focus Effect */
#student_search:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

/* Button Hover Effects */
#select_all_btn:hover,
#prev_page_btn:hover,
#next_page_btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4);
}

#deselect_all_btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
}

#prev_page_btn:disabled,
#next_page_btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

#prev_page_btn:disabled:hover,
#next_page_btn:disabled:hover {
    transform: none;
    box-shadow: none;
}

button[type="submit"]:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.5);
}

/* Student List Item Styles */
.student-checkbox-item {
    padding: 12px 14px;
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s ease;
    cursor: pointer;
    border-radius: 4px;
    margin-bottom: 2px;
    max-width: 100%;
    overflow-x: hidden;
}

.student-checkbox-item:hover {
    background: #f3f4f6;
    transform: translateX(2px);
}

.student-checkbox-item:last-child {
    border-bottom: none;
}

.student-checkbox-item label {
    cursor: pointer;
    margin: 0;
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

.student-checkbox-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-right: 12px;
    cursor: pointer;
    accent-color: #667eea;
    flex-shrink: 0;
}

.student-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex: 1;
    min-width: 0;
    overflow-x: hidden;
}

.student-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    flex: 1;
    min-width: 0;
}

.student-code {
    color: #6b7280;
    font-size: 12px;
    background: #f3f4f6;
    padding: 4px 10px;
    border-radius: 4px;
    font-weight: 500;
    white-space: nowrap;
    flex-shrink: 0;
    margin-left: 8px;
}

/* Custom Scrollbar for Students List */
#students_list::-webkit-scrollbar {
    width: 8px;
}

#students_list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#students_list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

#students_list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Select2 Dropdown Styling */
.select2-container--default .select2-selection--single {
    border: 2px solid #dee2e6 !important;
    border-radius: 6px !important;
    height: 42px !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    padding-left: 12px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
}

/* Mobile Responsive for Header Buttons */
@media (max-width: 576px) {
    #student_selection_section > div > div:first-child {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    #student_selection_section > div > div:first-child > div:last-child {
        width: 100%;
        justify-content: flex-start;
    }
    
    #student_selection_section button {
        flex: 1;
        min-width: 0;
    }
}
</style>

<script>
$(document).ready(function() {
    let studentsData = [];
    let filteredStudents = [];
    let currentPage = 1;
    const studentsPerPage = 10; // Show 10 students per page
    let selectedStudentIds = new Set(); // Track selected students across pages

    

    // Load students when class is selected
    $('#class_id').change(function() {
        const classId = $(this).val();
        if(!classId) {
            $('#student_selection_section').hide();
            return;
        }

        $('#student_selection_section').show();
        $('#students_list').html('<div style="text-align: center; padding: 20px;"><i class="fa fa-spinner fa-spin" style="font-size: 24px; color: #667eea;"></i><p style="margin-top: 10px; color: #6b7280;">Loading students...</p></div>');

        $.ajax({
            url: '<?php echo site_url("admin/get_students_for_terminal_bill_by_class_json"); ?>',
            type: 'POST',
            data: { class_id: classId },
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success' && response.students.length > 0) {
                studentsData = response.students;
                filteredStudents = studentsData;
                currentPage = 1;
                selectedStudentIds.clear(); // Clear selections when class changes
                renderPage();
            } else {
                $('#students_list').html('<div style="text-align: center; padding: 20px; color: #9ca3af;"><i class="fa fa-user-slash" style="font-size: 24px;"></i><p style="margin-top: 10px;">No students found in this class</p></div>');
                $('#pagination_controls').hide();
            }
        }).fail(function() {
            $('#students_list').html('<div style="text-align: center; padding: 20px; color: #ef4444;"><i class="fa fa-exclamation-triangle" style="font-size: 24px;"></i><p style="margin-top: 10px;">Failed to load students</p></div>');
            $('#pagination_controls').hide();
        });
    });

    // Render current page
    function renderPage() {
        const totalPages = Math.ceil(filteredStudents.length / studentsPerPage);
        const startIndex = (currentPage - 1) * studentsPerPage;
        const endIndex = startIndex + studentsPerPage;
        const pageStudents = filteredStudents.slice(startIndex, endIndex);

        let html = '';
        pageStudents.forEach(function(student) {
            const isChecked = selectedStudentIds.has(student.student_id) ? 'checked' : '';
            html += `
                <div class="student-checkbox-item">
                    <label>
                        <input type="checkbox" name="student_ids[]" value="${student.student_id}" class="student-checkbox" ${isChecked}>
                        <div class="student-info">
                            <span class="student-name">${student.name}</span>
                            <span class="student-code">${student.student_code}</span>
                        </div>
                    </label>
                </div>
            `;
        });
        $('#students_list').html(html);

        // Update pagination info
        $('#current_page').text(currentPage);
        $('#total_pages').text(totalPages);
        const actualEndIndex = Math.min(endIndex, filteredStudents.length);
        const showingText = `${startIndex + 1}-${actualEndIndex}`;
        $('#showing_count').text(showingText);
        $('#total_count').text(filteredStudents.length);

        // Enable/disable pagination buttons
        $('#prev_page_btn').prop('disabled', currentPage === 1);
        $('#next_page_btn').prop('disabled', currentPage === totalPages || totalPages === 0);

        // Show/hide pagination controls
        if(filteredStudents.length > studentsPerPage) {
            $('#pagination_controls').show();
        } else {
            $('#pagination_controls').hide();
        }

        updateSelectedCount();
    }

    // Previous page button
    $('#prev_page_btn').click(function() {
        if(currentPage > 1) {
            currentPage--;
            renderPage();
            // Scroll to top of students list
            $('#students_list').scrollTop(0);
        }
    });

    // Next page button
    $('#next_page_btn').click(function() {
        const totalPages = Math.ceil(filteredStudents.length / studentsPerPage);
        if(currentPage < totalPages) {
            currentPage++;
            renderPage();
            // Scroll to top of students list
            $('#students_list').scrollTop(0);
        }
    });

    // Search students
    $('#student_search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filteredStudents = studentsData.filter(function(student) {
            return student.name.toLowerCase().includes(searchTerm) || 
                   student.student_code.toLowerCase().includes(searchTerm);
        });
        currentPage = 1; // Reset to first page on search
        renderPage();
    });

    // Select all (on current page and all pages)
    $('#select_all_btn').click(function() {
        // Select all students in the entire dataset
        filteredStudents.forEach(function(student) {
            selectedStudentIds.add(student.student_id);
        });
        // Update checkboxes on current page
        $('.student-checkbox').prop('checked', true);
        updateSelectedCount();
    });

    // Deselect all
    $('#deselect_all_btn').click(function() {
        selectedStudentIds.clear();
        $('.student-checkbox').prop('checked', false);
        updateSelectedCount();
    });

    // Update selected count and track selections
    $(document).on('change', '.student-checkbox', function() {
        const studentId = $(this).val();
        if($(this).is(':checked')) {
            selectedStudentIds.add(studentId);
        } else {
            selectedStudentIds.delete(studentId);
        }
        updateSelectedCount();
    });

    function updateSelectedCount() {
        $('#selected_count').text(selectedStudentIds.size);
    }

    // Form submission
    $('#terminal_bills_form').submit(function(e) {
        e.preventDefault();

        if(selectedStudentIds.size === 0) {
            showAjaxModal_alert('Please select at least one student', 'error');
            return;
        }

        // Add student_ids as hidden input to the actual form
        $(this).find('input[name="student_ids"]').remove();
        $(this).append('<input type="hidden" name="student_ids" value="' + Array.from(selectedStudentIds).join(',') + '">');

        // Submit the actual form (with CSRF token)
        this.submit();

        showAjaxModal_alert('Generating report...', 'success', false);
    });

    // Auto-populate class from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const classIdParam = urlParams.get('class_id');
    if(classIdParam) {
        // Set the class dropdown value
        $('#class_id').val(classIdParam);
        // Trigger Select2 update
        $('#class_id').trigger('change.select2');
        // Trigger the change event to load students
        $('#class_id').trigger('change');
    }
});
</script>
