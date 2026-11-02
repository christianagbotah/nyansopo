<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <h3 class="panel-title" style="color: white;">
                    <i class="fa fa-edit"></i> Record Student Marks
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label>Exam</label>
                        <select id="exam_id" class="form-control" onchange="loadSubjects()">
                            <option value="">Select Exam</option>
                            <?php
                            $exams = $this->db->get('enterprise_exams')->result_array();
                            foreach($exams as $exam):
                            ?>
                            <option value="<?php echo $exam['exam_id']; ?>" <?php echo ($exam_id == $exam['exam_id']) ? 'selected' : ''; ?>>
                                <?php echo $exam['exam_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Subject</label>
                        <select id="subject_id" class="form-control" onchange="loadStudents()">
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button onclick="loadStudents()" class="btn btn-primary btn-block">
                            <i class="fa fa-search"></i> Load Students
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button onclick="saveAllMarks()" class="btn btn-success btn-block">
                            <i class="fa fa-save"></i> Save All Marks
                        </button>
                    </div>
                </div>

                <!-- Students Table -->
                <div id="students-container" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead style="background: #667eea; color: white;">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">Student Name</th>
                                    <th width="15%">Class Score (30)</th>
                                    <th width="15%">Exam Score (70)</th>
                                    <th width="10%">Total</th>
                                    <th width="10%">Grade</th>
                                    <th width="15%">Remark</th>
                                </tr>
                            </thead>
                            <tbody id="students-list"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    <?php if($exam_id): ?>
    loadSubjects();
    <?php endif; ?>
});

function loadSubjects() {
    const examId = $('#exam_id').val();
    if(!examId) return;
    
    $.ajax({
        url: '<?php echo site_url("examination/get_exam_subjects"); ?>',
        type: 'GET',
        data: {exam_id: examId},
        dataType: 'json',
        success: function(response) {
            let html = '<option value="">Select Subject</option>';
            response.data.forEach(subject => {
                const selected = subject.subject_id == '<?php echo $subject_id; ?>' ? 'selected' : '';
                html += `<option value="${subject.subject_id}" ${selected}>${subject.name}</option>`;
            });
            $('#subject_id').html(html);
            
            <?php if($subject_id): ?>
            loadStudents();
            <?php endif; ?>
        }
    });
}

function loadStudents() {
    const examId = $('#exam_id').val();
    const subjectId = $('#subject_id').val();
    
    if(!examId || !subjectId) {
        showAjaxModal_alert('Please select exam and subject', 'warning');
        return;
    }
    
    showAjaxModal_alert('Loading students...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("examination/get_students_for_marks"); ?>',
        type: 'GET',
        data: {exam_id: examId, subject_id: subjectId},
        dataType: 'json',
        success: function(response) {
            $('.close')[0].click();
            displayStudents(response.data);
            $('#students-container').show();
        }
    });
}

function displayStudents(students) {
    let html = '';
    students.forEach((student, index) => {
        html += `
        <tr>
            <td>${index + 1}</td>
            <td>${student.name}</td>
            <td>
                <input type="number" class="form-control class-score" 
                       data-student="${student.student_id}" 
                       value="${student.class_score || ''}" 
                       min="0" max="30" step="0.01"
                       onchange="calculateTotal(this)">
            </td>
            <td>
                <input type="number" class="form-control exam-score" 
                       data-student="${student.student_id}" 
                       value="${student.exam_score || ''}" 
                       min="0" max="70" step="0.01"
                       onchange="calculateTotal(this)">
            </td>
            <td>
                <input type="text" class="form-control total-score" 
                       data-student="${student.student_id}" 
                       value="${student.total_score || ''}" readonly>
            </td>
            <td>
                <input type="text" class="form-control grade" 
                       data-student="${student.student_id}" 
                       value="${student.grade || ''}" readonly>
            </td>
            <td>
                <input type="text" class="form-control remark" 
                       data-student="${student.student_id}" 
                       value="${student.remark || ''}" readonly>
            </td>
        </tr>`;
    });
    $('#students-list').html(html);
}

function calculateTotal(input) {
    const studentId = $(input).data('student');
    const classScore = parseFloat($(`.class-score[data-student="${studentId}"]`).val()) || 0;
    const examScore = parseFloat($(`.exam-score[data-student="${studentId}"]`).val()) || 0;
    const total = classScore + examScore;
    
    $(`.total-score[data-student="${studentId}"]`).val(total.toFixed(2));
    
    // Get grade
    const grade = getGrade(total);
    $(`.grade[data-student="${studentId}"]`).val(grade.grade);
    $(`.remark[data-student="${studentId}"]`).val(grade.remark);
}

function getGrade(score) {
    if(score >= 80) return {grade: 'A1', remark: 'Excellent'};
    if(score >= 70) return {grade: 'B2', remark: 'Very Good'};
    if(score >= 65) return {grade: 'B3', remark: 'Good'};
    if(score >= 60) return {grade: 'C4', remark: 'Credit'};
    if(score >= 55) return {grade: 'C5', remark: 'Credit'};
    if(score >= 50) return {grade: 'C6', remark: 'Credit'};
    if(score >= 45) return {grade: 'D7', remark: 'Pass'};
    if(score >= 40) return {grade: 'E8', remark: 'Pass'};
    return {grade: 'F9', remark: 'Fail'};
}

function saveAllMarks() {
    const examId = $('#exam_id').val();
    const subjectId = $('#subject_id').val();
    
    if(!examId || !subjectId) {
        showAjaxModal_alert('Please select exam and subject', 'warning');
        return;
    }
    
    const marks = [];
    $('#students-list tr').each(function() {
        const studentId = $(this).find('.class-score').data('student');
        const classScore = $(this).find('.class-score').val();
        const examScore = $(this).find('.exam-score').val();
        
        if(classScore || examScore) {
            marks.push({
                student_id: studentId,
                class_score: classScore || 0,
                exam_score: examScore || 0
            });
        }
    });
    
    if(marks.length === 0) {
        showAjaxModal_alert('No marks to save', 'warning');
        return;
    }
    
    showAjaxModal_alert('Saving marks...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("examination/save_marks"); ?>',
        type: 'POST',
        data: {
            exam_id: examId,
            subject_id: subjectId,
            marks: marks
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => loadStudents(), 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('An error occurred', 'error');
        }
    });
}
</script>
