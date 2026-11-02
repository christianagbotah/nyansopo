<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.modern-form-group { margin-bottom: 28px; }
.modern-label { display: block; font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 12px; letter-spacing: 0.3px; }
.modern-label i { color: #667eea; margin-right: 8px; }
.modern-input { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.2s; background: #ffffff; color: #1a202c; font-weight: 500; }
.modern-input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); background: #f7fafc; }
.modern-btn { padding: 16px 32px; border-radius: 10px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.info-banner { background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; padding: 20px; border-radius: 10px; margin-bottom: 24px; }
.step-card { background: white; border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px; transition: all 0.3s; }
.step-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
.step-number { width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; margin-right: 16px; }
</style>

<div class="info-banner">
    <h3 style="margin: 0 0 12px 0; color: #1e40af; font-size: 20px; font-weight: 700;">
        <i class="fa fa-info-circle"></i> Bulk Arrears Import - Enterprise Solution
    </h3>
    <p style="margin: 0; color: #1e3a8a; font-size: 14px; line-height: 1.6;">
        Import arrears for multiple students efficiently. Generate a template with all active students, fill in the amounts, and upload to create invoices automatically.
    </p>
</div>

<div class="step-card">
    <div style="display: flex; align-items: flex-start;">
        <div class="step-number">1</div>
        <div style="flex: 1;">
            <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #2d3748;">Generate Template</h4>
            <p style="margin: 0 0 16px 0; color: #6b7280; font-size: 14px;">
                Download an Excel template with all active students for the current academic period. You can select all students or customize your selection.
            </p>
            <button type="button" onclick="openStudentSelectionModal()" class="modern-btn btn-success">
                <i class="fa fa-file-excel"></i> Generate Arrears Template
            </button>
        </div>
    </div>
</div>

<div class="step-card">
    <div style="display: flex; align-items: flex-start;">
        <div class="step-number">2</div>
        <div style="flex: 1;">
            <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #2d3748;">Fill Template</h4>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                Open the downloaded Excel file and fill in the "Amount Owed" column for each student. Save the file when done.
            </p>
        </div>
    </div>
</div>

<div class="step-card">
    <div style="display: flex; align-items: flex-start;">
        <div class="step-number">3</div>
        <div style="flex: 1;">
            <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #2d3748;">Upload & Import</h4>
            <p style="margin: 0 0 16px 0; color: #6b7280; font-size: 14px;">
                Upload the completed Excel file to automatically create arrears invoices for all students with amounts.
            </p>
            
            <?php echo form_open_multipart('admin/bulk_arrears_import/process', array('id' => 'bulk_arrears_form')); ?>
                <div class="modern-form-group">
                    <label class="modern-label">
                        <i class="fa fa-upload"></i> Select Excel File
                    </label>
                    <input type="file" name="arrears_file" id="arrears_file" accept=".xlsx,.xls" class="modern-input" required>
                    <small style="color: #6b7280; display: block; margin-top: 8px;">
                        <i class="fa fa-info-circle"></i> Only Excel files (.xlsx, .xls) are accepted
                    </small>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="modern-btn btn-primary">
                        <i class="fa fa-cloud-upload-alt"></i> Upload & Process
                    </button>
                    <button type="button" onclick="$('.close').click()" class="modern-btn" style="background: #6b7280; color: white;">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
function openStudentSelectionModal() {
    $('.close').click();
    loadModalContent('budgetDetailsModal', '<?php echo site_url('admin/bulk_arrears_student_selection'); ?>', '<i class="fa fa-users"></i> Select Students for Arrears Template');
}

$('#bulk_arrears_form').submit(function(e) {
    e.preventDefault();
    
    const fileInput = $('#arrears_file')[0];
    if(!fileInput.files.length) {
        showAjaxModal_alert('Please select a file to upload', 'error');
        return;
    }
    
    $('.close').click();
    showAjaxModal_alert('Reading Excel file...', 'loading');
    
    const file = fileInput.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            
            const allStudents = [];
            let sheetsProcessed = 0;
            
            // Read ALL sheets except Instructions
            workbook.SheetNames.forEach(sheetName => {
                if(sheetName.toLowerCase() === 'instructions') return;
                
                sheetsProcessed++;
                const worksheet = workbook.Sheets[sheetName];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, {defval: ''});
                
                jsonData.forEach(row => {
                    // Check for Student ID and Amount Owed with flexible column names
                    const studentId = row['Student ID'] || row['student_id'] || row['StudentID'];
                    const amountOwed = row['Amount Owed'] || row['amount_owed'] || row['AmountOwed'];
                    
                    if(studentId && amountOwed && parseFloat(amountOwed) > 0) {
                        allStudents.push({
                            'Student ID': studentId,
                            'Student Name': row['Student Name'] || row['student_name'] || '',
                            'Class': row['Class'] || row['class'] || '',
                            'Amount Owed': parseFloat(amountOwed)
                        });
                    }
                });
            });
            
            if(allStudents.length === 0) {
                showAjaxModal_alert('No valid data found in the Excel file. Sheets processed: ' + sheetsProcessed, 'error');
                return;
            }
            
            showAjaxModal_alert('Processing ' + allStudents.length + ' student(s) from ' + sheetsProcessed + ' sheet(s)...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url('admin/bulk_arrears_import/process'); ?>',
                type: 'POST',
                data: { arrears_data: JSON.stringify(allStudents) },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    let msg = response.message;
                    if(response.details && response.details.errors > 0) {
                        msg += '\n\nErrors:\n' + response.details.error_messages.join('\n');
                    }
                    showAjaxModal_alert(msg, 'success');
                } else {
                    showAjaxModal_alert(response.message || 'Import failed', 'error');
                }
            }).fail(function(xhr) {
                showAjaxModal_alert('An error occurred during import', 'error');
            });
            
        } catch(error) {
            showAjaxModal_alert('Error reading Excel file: ' + error.message, 'error');
        }
    };
    
    reader.onerror = function() {
        showAjaxModal_alert('Failed to read file', 'error');
    };
    
    reader.readAsArrayBuffer(file);
});
</script>
