<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div><i class="fa fa-edit"></i> Record Marks</div>
                    <div id="network_status" style="padding: 5px 12px; background: rgba(255,255,255,0.2); border-radius: 15px; font-size: 12px;">
                        <i class="fa fa-wifi"></i> Online
                    </div>
                </div>
            </div>
            
            <div class="panel-body">
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <select id="exam_id" class="form-control" required>
                            <option value="">Select Exam</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button onclick="loadStudents()" class="btn btn-primary btn-block">Load Students</button>
                    </div>
                </div>
                
                <div id="marks_container"></div>
                
                <div style="position: fixed; bottom: 20px; right: 20px; z-index: 999;">
                    <button onclick="saveMarksOffline()" class="btn btn-success btn-lg" style="box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        <i class="fa fa-save"></i> Save Marks
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url('assets/js/attendance_offline.js'); ?>"></script>
<script>
const ExamsDB = {
    dbName: 'ExamsDB',
    version: 1,
    
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);
            request.onupgradeneeded = (e) => {
                const db = e.target.result;
                if(!db.objectStoreNames.contains('marks')) {
                    const store = db.createObjectStore('marks', {keyPath: 'id', autoIncrement: true});
                    store.createIndex('synced', 'synced', {unique: false});
                }
            };
        });
    },
    
    async save(records) {
        const db = await this.init();
        const tx = db.transaction(['marks'], 'readwrite');
        const store = tx.objectStore('marks');
        records.forEach(r => { r.synced = false; store.add(r); });
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve({status: 'success'});
            tx.onerror = () => reject(tx.error);
        });
    }
};

function loadStudents() {
    const examId = $('#exam_id').val();
    const subjectId = $('#subject_id').val();
    
    if(!examId || !subjectId) {
        showAjaxModal_alert('Select exam and subject', 'error');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url("examination/get_students_for_marks"); ?>',
        type: 'POST',
        data: {exam_id: examId, subject_id: subjectId},
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            renderMarksTable(response.data);
        }
    });
}

function renderMarksTable(students) {
    let html = `
    <table class="table table-bordered">
        <thead style="background: #f59e0b; color: white;">
            <tr>
                <th>Student</th>
                <th width="120">Class Score (30)</th>
                <th width="120">Exam Score (70)</th>
                <th width="100">Total</th>
                <th width="80">Grade</th>
            </tr>
        </thead>
        <tbody>`;
    
    students.forEach(s => {
        html += `
        <tr data-student-id="${s.student_id}">
            <td>${s.name}</td>
            <td><input type="number" class="form-control class-score" max="30" value="${s.class_score || ''}" onchange="calculateTotal(this)"></td>
            <td><input type="number" class="form-control exam-score" max="70" value="${s.exam_score || ''}" onchange="calculateTotal(this)"></td>
            <td><input type="text" class="form-control total-score" readonly></td>
            <td><input type="text" class="form-control grade" readonly></td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    $('#marks_container').html(html);
}

function calculateTotal(input) {
    const row = $(input).closest('tr');
    const classScore = parseFloat(row.find('.class-score').val()) || 0;
    const examScore = parseFloat(row.find('.exam-score').val()) || 0;
    const total = classScore + examScore;
    
    row.find('.total-score').val(total);
    row.find('.grade').val(getGrade(total));
}

function getGrade(score) {
    if(score >= 80) return 'A1';
    if(score >= 70) return 'B2';
    if(score >= 65) return 'B3';
    if(score >= 60) return 'C4';
    if(score >= 55) return 'C5';
    if(score >= 50) return 'C6';
    if(score >= 45) return 'D7';
    if(score >= 40) return 'E8';
    return 'F9';
}

async function saveMarksOffline() {
    const records = [];
    
    $('#marks_container tbody tr').each(function() {
        const classScore = $(this).find('.class-score').val();
        const examScore = $(this).find('.exam-score').val();
        
        if(classScore || examScore) {
            records.push({
                exam_id: $('#exam_id').val(),
                subject_id: $('#subject_id').val(),
                student_id: $(this).data('student-id'),
                class_score: classScore || 0,
                exam_score: examScore || 0,
                total_score: $(this).find('.total-score').val(),
                grade: $(this).find('.grade').val()
            });
        }
    });
    
    if(records.length === 0) {
        showAjaxModal_alert('No marks to save', 'error');
        return;
    }
    
    if(navigator.onLine) {
        saveToServer(records);
    } else {
        try {
            await ExamsDB.save(records);
            showAjaxModal_alert('Saved offline. Will sync when online.', 'success', false);
        } catch(error) {
            showAjaxModal_alert('Failed: ' + error.message, 'error');
        }
    }
}

function saveToServer(records) {
    showAjaxModal_alert('Saving...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("examination/save_marks"); ?>',
        type: 'POST',
        data: {marks: records},
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(async function() {
        await ExamsDB.save(records);
        showAjaxModal_alert('Network error. Saved offline.', 'warning');
    });
}

function updateNetworkStatus() {
    const indicator = $('#network_status');
    if(navigator.onLine) {
        indicator.html('<i class="fa fa-wifi"></i> Online').css('color', '#10b981');
    } else {
        indicator.html('<i class="fa fa-wifi-slash"></i> Offline').css('color', '#ef4444');
    }
}

$(document).ready(function() {
    updateNetworkStatus();
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
});
</script>
