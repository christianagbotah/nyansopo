<style>
@import url('<?php echo base_url(); ?>assets/cdn/fonts/inter.css');
* { font-family: 'Inter', sans-serif; }
.modern-container { max-width: 1400px; margin: 0 auto; padding: 24px; }
.page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2); }
.page-title { font-size: 32px; font-weight: 700; color: white !important; margin: 0 0 8px 0; letter-spacing: -0.5px; }
.page-subtitle { font-size: 16px; color: rgba(255,255,255,0.9); margin: 0; }
.filter-card { background: white; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
.form-group label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
.modern-input { border: 2px solid #e5e7eb; border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s; width: 100%; height: 46px; box-sizing: border-box; }
.modern-input:focus { border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); outline: none; }
.select2-container .select2-selection--single { height: 46px !important; border: 2px solid #e5e7eb !important; border-radius: 10px !important; }
.select2-container--classic .select2-selection--single .select2-selection__rendered { line-height: 42px !important; padding-left: 16px !important; }
.select2-container--classic .select2-selection--single .select2-selection__arrow { height: 42px !important; }
.btn-modern { padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; height: 46px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
.data-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; }
.table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
.table-modern thead th { background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%); padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; }
.table-modern tbody tr { transition: all 0.2s; }
.table-modern tbody tr:hover { background: #f9fafb; }
.table-modern tbody td { padding: 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
.debit { color: #dc2626; font-weight: 600; }
.credit { color: #10b981; font-weight: 600; }
.balance { font-weight: 700; color: #111827; }
.currency { font-size: 9px; font-weight: 500; opacity: 0.7; margin-right: 2px; }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-icon { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
</style>

<div class="modern-container">
<div class="page-header">
    <h1 class="page-title">📚 Student Ledger Report</h1>
    <p class="page-subtitle">Complete transaction history and account statements</p>
</div>

<div class="filter-card">
    <div class="filter-grid">
        <div class="form-group">
            <label>👤 Student</label>
            <div class="student-search-wrapper" style="position: relative;">
                <input type="text" id="student_search" placeholder="Search student by name or code..." autocomplete="off" class="modern-input" style="padding-left: 40px;">
                <input type="hidden" id="student_filter" name="student_filter">
                <div class="search-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 16px; pointer-events: none;">
                    <i class="fa fa-search"></i>
                </div>
                <div id="student_dropdown" class="hidden" style="position: absolute; width: 100%; background: white; border: 2px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-top: 4px; max-height: 320px; overflow-y: auto; z-index: 1000;"></div>
            </div>
        </div>
        <div class="form-group">
            <label>📅 Academic Year</label>
            <select id="year_filter" class="modern-input">
                <?php
                $years = $this->db->distinct()->select('year')->order_by('year', 'DESC')->get('invoice')->result_array();
                foreach($years as $year):
                ?>
                <option value="<?php echo $year['year']; ?>"><?php echo $year['year']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>📆 Term</label>
            <select id="term_filter" class="modern-input">
                <option value="">All Terms</option>
                <option value="1">Term 1</option>
                <option value="2">Term 2</option>
                <option value="3">Term 3</option>
            </select>
        </div>
    </div>
    <div style="display: flex; gap: 12px; margin-top: 20px;">
        <button onclick="loadLedger()" class="btn-modern btn-primary">
            <i class="fa fa-search"></i> Generate Report
        </button>
        <button onclick="exportLedger()" class="btn-modern btn-success">
            <i class="fa fa-file-excel"></i> Export to Excel
        </button>
    </div>
</div>

<div id="ledger-content"></div>
</div>

<script>
$(function() {
    // Initialize year filter
    loadLedger();
    
    // AJAX Student Search
    let searchTimeout;
    $('#student_search').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val().trim();
        
        if(searchTerm.length < 2) {
            $('#student_dropdown').addClass('hidden').html('');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '<?php echo base_url(); ?>admin/search_all_students',
                type: 'POST',
                data: { search_term: searchTerm },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success' && response.students.length > 0) {
                        let html = '';
                        response.students.forEach(function(student) {
                            html += '<div class="student-item" data-id="' + student.student_id + '" style="padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: all 0.2s;">';
                            html += '<div style="font-weight: 600; color: #111827; margin-bottom: 2px;">' + student.name + '</div>';
                            html += '<div style="font-size: 12px; color: #6b7280;">' + student.student_code + ' • ' + student.class_name + '</div>';
                            html += '</div>';
                        });
                        $('#student_dropdown').removeClass('hidden').html(html);
                        
                        // Handle student selection
                        $('.student-item').on('click', function() {
                            const studentId = $(this).data('id');
                            const studentName = $(this).find('div:first').text();
                            $('#student_filter').val(studentId);
                            $('#student_search').val(studentName);
                            $('#student_dropdown').addClass('hidden').html('');
                        });
                        
                        // Hover effect
                        $('.student-item').hover(
                            function() { $(this).css('background', '#f9fafb'); },
                            function() { $(this).css('background', 'white'); }
                        );
                    } else {
                        $('#student_dropdown').removeClass('hidden').html('<div style="padding: 16px; text-align: center; color: #9ca3af;">No students found</div>');
                    }
                }
            });
        }, 300);
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if(!$(e.target).closest('.student-search-wrapper').length) {
            $('#student_dropdown').addClass('hidden').html('');
        }
    });
    
    // Clear selection when input is cleared
    $('#student_search').on('keyup', function() {
        if($(this).val().trim() === '') {
            $('#student_filter').val('');
        }
    });
});

function loadLedger() {
    $.ajax({
        url: '<?php echo base_url(); ?>admin/get_student_ledger',
        type: 'POST',
        data: {
            student_id: $('#student_filter').val(),
            year: $('#year_filter').val(),
            term: $('#term_filter').val()
        },
        dataType: 'json',
        beforeSend: function() {
            $('#ledger-content').html('<div class="empty-state"><div class="empty-icon"><i class="fa fa-spinner fa-spin"></i></div><p>Loading ledger data...</p></div>');
        }
    }).done(function(response) {
        if(response.status === 'success') {
            $('#ledger-content').html(response.html);
            
            // Initialize DataTables with pagination and filters
            if($.fn.DataTable.isDataTable('#ledgerTable')) {
                $('#ledgerTable').DataTable().destroy();
            }
            $('#ledgerTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'desc']],
                language: {
                    search: "Search transactions:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ transactions",
                    infoEmpty: "No transactions found",
                    infoFiltered: "(filtered from _MAX_ total transactions)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"top"lf>rt<"bottom"ip><"clear">'
            });
            
            // Auto-scroll to results
            $('html, body').animate({
                scrollTop: $('#ledger-content').offset().top - 20
            }, 500);
        } else {
            $('#ledger-content').html('<div class="empty-state"><div class="empty-icon"><i class="fa fa-exclamation-triangle"></i></div><p>' + (response.message || 'Error loading data') + '</p></div>');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        console.error('Response:', xhr.responseText);
        $('#ledger-content').html('<div class="empty-state"><div class="empty-icon"><i class="fa fa-exclamation-triangle"></i></div><p>Error loading ledger data. Please check console for details.</p></div>');
    });
}

function exportLedger() {
    window.location.href = '<?php echo base_url(); ?>admin/export_student_ledger?' + 
        'student_id=' + $('#student_filter').val() + 
        '&year=' + $('#year_filter').val() + 
        '&term=' + $('#term_filter').val();
}
</script>
