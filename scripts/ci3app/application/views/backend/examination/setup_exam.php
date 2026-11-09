<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <h3 class="panel-title" style="color: white;">
                    <i class="fa fa-cog"></i> Setup New Examination
                </h3>
            </div>
            <div class="panel-body">
                
                <form id="exam-setup-form" action="<?php echo site_url('examination/create_exam'); ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Exam Name *</label>
                                <input type="text" name="exam_name" class="form-control" required placeholder="e.g., First Term Examination 2024">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Exam Type *</label>
                                <select name="exam_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="mid_term">Mid Term</option>
                                    <option value="end_term">End Term</option>
                                    <option value="mock">Mock Exam</option>
                                    <option value="waec">WAEC</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Class *</label>
                                <select name="class_id" class="form-control" required>
                                    <option value="">Select Class</option>
                                    <?php
                                    $classes = $this->db->get('class')->result_array();
                                    foreach($classes as $class):
                                    ?>
                                    <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Academic Year *</label>
                                <input type="text" name="year" class="form-control" required value="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Term *</label>
                                <select name="term" class="form-control" required>
                                    <option value="">Select Term</option>
                                    <option value="1">First Term</option>
                                    <option value="2">Second Term</option>
                                    <option value="3">Third Term</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <i class="fa fa-save"></i> Create Examination
                        </button>
                        <a href="<?php echo site_url('examination'); ?>" class="btn btn-lg btn-default">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php $this->load->view('backend/examination/subject_modal'); ?>

<script>
$('#exam-setup-form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Creating examination...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('.close')[0].click();
            showAjaxModal_alert('Exam created! Now add subjects...', 'success', false);
            setTimeout(() => showSubjectModal(response.exam_id), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});
</script>
