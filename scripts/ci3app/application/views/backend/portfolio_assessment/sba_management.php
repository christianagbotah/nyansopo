<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border: none;">
                <h3 class="panel-title" style="color: white;">
                    <i class="fa fa-graduation-cap"></i> SBA Management (Auto-Filled from Portfolio)
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Filter Form -->
                <form id="sba-filter-form" class="row">
                    <div class="col-md-3">
                        <label>Class</label>
                        <select id="class_id" class="form-control" onchange="loadSubjects()">
                            <option value="">Select Class</option>
                            <?php
                            $classes = $this->db->get('class')->result_array();
                            foreach($classes as $class):
                            ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name'].' '.$class['name_numeric']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Subject</label>
                        <select id="subject_id" class="form-control">
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Year</label>
                        <input type="text" id="year" class="form-control" value="<?php echo $this->db->get_where('settings', ['type' => 'running_year'])->row()->description; ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Term</label>
                        <select id="term" class="form-control">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="button" onclick="loadSBAData()" class="btn btn-success btn-block">
                            <i class="fa fa-search"></i> Load
                        </button>
                    </div>
                </form>

                <!-- SBA Data Table -->
                <div id="sba-container" class="mt-4" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <strong>Class Test</strong> column is auto-filled from Portfolio Assessment. 
                        Portfolio Average × Weight (30%) = Class Test Score
                    </div>
                    
                    <table class="table table-bordered table-hover">
                        <thead style="background: #43e97b; color: white;">
                            <tr>
                                <th>Student</th>
                                <th>Portfolio Avg</th>
                                <th>Class Test (Auto)</th>
                                <th>Project Work</th>
                                <th>Homework</th>
                                <th>Group Work</th>
                                <th>Total SBA</th>
                                <th>Last Sync</th>
                            </tr>
                        </thead>
                        <tbody id="sba-list"></tbody>
                    </table>
                    
                    <button onclick="syncAllSBA()" class="btn btn-primary">
                        <i class="fa fa-sync"></i> Sync All from Portfolio
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function loadSubjects() {
    const classId = $('#class_id').val();
    if(!classId) return;
    
    $.ajax({
        url: '<?php echo site_url("examination/get_all_subjects"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let html = '<option value="">Select Subject</option>';
            response.data.forEach(subject => {
                html += `<option value="${subject.subject_id}">${subject.name}</option>`;
            });
            $('#subject_id').html(html);
        }
    });
}

function loadSBAData() {
    const classId = $('#class_id').val();
    const subjectId = $('#subject_id').val();
    const year = $('#year').val();
    const term = $('#term').val();
    
    if(!classId || !subjectId) {
        showAjaxModal_alert('Please select class and subject', 'Warning', false, true);
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url("portfolio_enterprise/get_sba_data"); ?>',
        type: 'GET',
        data: {class_id: classId, subject_id: subjectId, year: year, term: term},
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                displaySBAData(response.data);
                $('#sba-container').show();
            }
        }
    });
}

function displaySBAData(data) {
    let html = '';
    
    if(data.length === 0) {
        html = '<tr><td colspan="8" class="text-center">No SBA data found. Portfolio assessments will auto-fill when saved.</td></tr>';
    } else {
        data.forEach(row => {
            const autoFilled = row.auto_filled == 1 ? '<span class="badge badge-success">Auto</span>' : '';
            html += `
            <tr>
                <td>${row.student_name}</td>
                <td><strong>${row.term_average || 0}%</strong></td>
                <td>${row.class_test || 0} ${autoFilled}</td>
                <td>${row.project_work || 0}</td>
                <td>${row.homework || 0}</td>
                <td>${row.group_work || 0}</td>
                <td><strong>${row.total_sba || 0}</strong></td>
                <td>${row.last_sync_at || 'Never'}</td>
            </tr>`;
        });
    }
    
    $('#sba-list').html(html);
}

function syncAllSBA() {
    const classId = $('#class_id').val();
    const subjectId = $('#subject_id').val();
    const year = $('#year').val();
    const term = $('#term').val();
    
    showCustomConfirm('Sync all students SBA from portfolio?', function() {
        showAjaxModal_alert('Syncing...', 'loading');
        
        $.ajax({
            url: '<?php echo site_url("portfolio_enterprise/batch_sync_sba"); ?>',
            type: 'POST',
            data: {class_id: classId, subject_id: subjectId, year: year, term: term},
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert('SBA synced successfully', 'success');
                    setTimeout(() => loadSBAData(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }
        });
    }); // Close showCustomConfirm callback
}
</script>
