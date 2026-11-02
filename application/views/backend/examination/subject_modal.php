<div class="modal fade" id="subjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-book"></i> Add Subjects to Exam</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_exam_id">
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Core Subjects</h5>
                        <div id="core-subjects"></div>
                    </div>
                    <div class="col-md-6">
                        <h5>Elective Subjects</h5>
                        <div id="elective-subjects"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveExamSubjects()">
                    <i class="fa fa-save"></i> Save Subjects
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showSubjectModal(examId) {
    $('#modal_exam_id').val(examId);
    
    $.ajax({
        url: '<?php echo site_url("examination/get_all_subjects"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let coreHtml = '';
            let electiveHtml = '';
            
            response.data.forEach(subject => {
                const checkbox = `
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="subjects[]" value="${subject.subject_id}">
                            ${subject.name}
                        </label>
                    </div>`;
                
                if(subject.subject_type === 'core') {
                    coreHtml += checkbox;
                } else {
                    electiveHtml += checkbox;
                }
            });
            
            $('#core-subjects').html(coreHtml);
            $('#elective-subjects').html(electiveHtml);
            $('#subjectModal').modal('show');
        }
    });
}

function saveExamSubjects() {
    const examId = $('#modal_exam_id').val();
    const subjectIds = [];
    
    $('input[name="subjects[]"]:checked').each(function() {
        subjectIds.push($(this).val());
    });
    
    if(subjectIds.length === 0) {
        showAjaxModal_alert('Please select at least one subject', 'warning');
        return;
    }
    
    $('#subjectModal').modal('hide');
    showAjaxModal_alert('Adding subjects...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("examination/add_exam_subjects"); ?>',
        type: 'POST',
        data: {
            exam_id: examId,
            subject_ids: subjectIds
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        }
    });
}
</script>
