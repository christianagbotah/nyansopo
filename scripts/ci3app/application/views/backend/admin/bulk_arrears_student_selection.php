<style>
.student-list { max-height: 500px; overflow-y: auto; background: #f8f9fa; border-radius: 10px; padding: 16px; }
.student-item { background: white; border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; display: flex; align-items: center; transition: all 0.2s; }
.student-item:hover { border-color: #667eea; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1); }
.student-item input[type="checkbox"] { width: 20px; height: 20px; margin-right: 12px; cursor: pointer; }
.student-info { flex: 1; }
.student-name { font-weight: 600; color: #2d3748; font-size: 15px; }
.student-meta { font-size: 13px; color: #6b7280; margin-top: 4px; }
.action-bar { background: white; padding: 16px; border-radius: 10px; margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
@media (max-width: 768px) { .action-bar { flex-direction: column; } .action-bar > * { width: 100%; } }
</style>

<script>
function selectAllStudents() {
    $('.student-checkbox').prop('checked', true);
    updateSelectedCount();
}

function deselectAllStudents() {
    $('.student-checkbox').prop('checked', false);
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = $('.student-checkbox:checked').length;
    $('#selected_count').text(count);
}

function generateArrearsTemplate() {
    const selectedStudentIds = [];
    $('.student-checkbox:checked').each(function() {
        selectedStudentIds.push($(this).val());
    });
    
    if(selectedStudentIds.length === 0) {
        showAjaxModal_alert('Please select at least one student', 'error');
        return;
    }
    
    $('.close').click();
    showAjaxModal_alert('Generating template...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/generate_arrears_template'); ?>',
        type: 'POST',
        data: { student_ids: selectedStudentIds },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('.close').click();
            generateExcelFile(response.students);
            toastr.success('Template generated with ' + response.count + ' student(s)');
        } else {
            showAjaxModal_alert(response.message || 'Failed to generate template', 'error');
        }
    }).fail(function(xhr) {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function generateExcelFile(students) {
    const wb = XLSX.utils.book_new();
    
    // First sheet: Instructions
    const instructions = [
        ['BULK ARREARS IMPORT - INSTRUCTIONS'],
        [''],
        ['1. Each class has its own sheet - fill in the "Amount Owed" column for students'],
        ['2. Enter 0 or leave blank for students with no arrears'],
        ['3. Do NOT modify Student ID, Name, or Student Code columns'],
        ['4. You can fill multiple class sheets before uploading'],
        ['5. Upload the entire file - all sheets will be imported at once'],
        [''],
        ['IMPORTANT NOTES:'],
        ['- Only students with amounts greater than 0 will have invoices created'],
        ['- Each invoice will be created with "Arrears" as the bill item'],
        ['- Invoices will be created for the current academic term and year'],
        ['- All class sheets in this file will be processed during import'],
        [''],
        ['For support, contact your system administrator']
    ];
    const wsInstructions = XLSX.utils.aoa_to_sheet(instructions);
    wsInstructions['!cols'] = [{wch: 70}];
    XLSX.utils.book_append_sheet(wb, wsInstructions, 'Instructions');
    
    // Group students by class
    const studentsByClass = {};
    students.forEach(student => {
        if(!studentsByClass[student.class]) {
            studentsByClass[student.class] = [];
        }
        studentsByClass[student.class].push(student);
    });
    
    // Create a sheet for each class
    Object.keys(studentsByClass).forEach(className => {
        const classStudents = studentsByClass[className];
        const data = [['Student ID', 'Student Name', 'Student Code', 'Amount Owed']];
        
        classStudents.forEach(student => {
            data.push([student.student_id, student.name, student.student_code, 0.00]);
        });
        
        const ws = XLSX.utils.aoa_to_sheet(data);
        ws['!cols'] = [{wch: 12}, {wch: 30}, {wch: 15}, {wch: 15}];
        
        // Sanitize sheet name (Excel has 31 char limit and doesn't allow certain chars)
        let sheetName = className.substring(0, 31).replace(/[\\\/?\*\[\]]/g, '');
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
    });
    
    const filename = 'Arrears_Template_' + new Date().toISOString().slice(0,19).replace(/:/g,'-') + '.xlsx';
    XLSX.writeFile(wb, filename);
}

$(document).ready(function() {
    updateSelectedCount();
    
    $('#search_students').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.student-item').each(function() {
            const name = $(this).find('.student-checkbox').data('name').toLowerCase();
            const code = $(this).find('.student-checkbox').data('code').toLowerCase();
            const classname = $(this).find('.student-checkbox').data('class').toLowerCase();
            
            if(name.includes(searchTerm) || code.includes(searchTerm) || classname.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Delegate event for dynamically loaded checkboxes
    $(document).on('change', '.student-checkbox', function() {
        updateSelectedCount();
    });
});
</script>

<div class="action-bar">
    <input type="text" id="search_students" placeholder="Search by name or student code..." class="modern-input" style="flex: 1; margin: 0; min-width: 200px;">
    <button type="button" onclick="selectAllStudents()" class="modern-btn btn-success" style="padding: 12px 20px;">
        <i class="fa fa-check-square"></i> Select All
    </button>
    <button type="button" onclick="deselectAllStudents()" class="modern-btn" style="padding: 12px 20px; background: #ef4444; color: white;">
        <i class="fa fa-square"></i> Deselect All
    </button>
    <button type="button" onclick="generateArrearsTemplate()" class="modern-btn btn-primary" id="generate_btn" style="padding: 12px 20px;">
        <i class="fa fa-file-excel"></i> Generate
    </button>
    <button type="button" onclick="$('.close').click()" class="modern-btn" style="padding: 12px 20px; background: #6b7280; color: white;">
        <i class="fa fa-times"></i> Cancel
    </button>
</div>

<div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
    <p style="margin: 0; color: #1e40af; font-weight: 600;">
        <i class="fa fa-info-circle"></i> Selected: <span id="selected_count">0</span> student(s)
    </p>
</div>

<div class="student-list" id="student_list">
    <?php if(empty($students)): ?>
        <div style="text-align: center; padding: 40px; color: #9ca3af;">
            <i class="fa fa-users" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
            <p style="font-size: 16px; font-weight: 600;">No active students found</p>
        </div>
    <?php else: ?>
        <?php foreach($students as $student): ?>
            <div class="student-item">
                <input type="checkbox" class="student-checkbox" value="<?php echo $student['student_id']; ?>" 
                       data-name="<?php echo htmlspecialchars($student['name']); ?>" 
                       data-code="<?php echo htmlspecialchars($student['student_code']); ?>"
                       data-class="<?php echo htmlspecialchars($student['class_name']); ?>">
                <div class="student-info">
                    <div class="student-name"><?php echo $student['name']; ?></div>
                    <div class="student-meta">
                        <i class="fa fa-id-card"></i> <?php echo $student['student_code']; ?> &nbsp;|&nbsp;
                        <i class="fa fa-school"></i> <?php echo $student['class_name']; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
    <button type="button" onclick="generateArrearsTemplate()" class="modern-btn btn-primary" id="generate_btn">
        <i class="fa fa-file-excel"></i> Generate Template
    </button>
    <button type="button" onclick="$('.close').click()" class="modern-btn" style="background: #6b7280; color: white;">
        <i class="fa fa-times"></i> Cancel
    </button>
</div>
