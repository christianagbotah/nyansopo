<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <h3 class="panel-title" style="color: white;">
                    <i class="fa fa-clipboard-list"></i> Portfolio Assessment (Enterprise)
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <a href="#" onclick="manageScores()" class="btn btn-block" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px;">
                            <i class="fa fa-edit"></i><br>Manage Scores
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo site_url('portfolio_enterprise/sba_management'); ?>" class="btn btn-block" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 15px;">
                            <i class="fa fa-graduation-cap"></i><br>SBA Management
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-block" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 15px;">
                            <i class="fa fa-chart-line"></i><br>Analytics
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo site_url('admin/portfolio_assessment_manage'); ?>" class="btn btn-block btn-default" style="padding: 15px;">
                            <i class="fa fa-arrow-left"></i><br>Classic View
                        </a>
                    </div>
                </div>

                <!-- Filter Form -->
                <form id="filter-form" class="row">
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
                        <label>Week</label>
                        <input type="week" id="week" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="button" onclick="manageScores()" class="btn btn-success btn-block">
                            <i class="fa fa-edit"></i> Manage
                        </button>
                    </div>
                </form>

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

function manageScores() {
    const classId = $('#class_id').val();
    const subjectId = $('#subject_id').val();
    const week = $('#week').val();
    
    if(!classId || !subjectId || !week) {
        showAjaxModal_alert('Please select class, subject and week', 'Warning', false, true);
        return;
    }
    
    window.location.href = `<?php echo site_url('portfolio_enterprise/manage'); ?>?class_id=${classId}&subject_id=${subjectId}&week=${week}`;
}
</script>
