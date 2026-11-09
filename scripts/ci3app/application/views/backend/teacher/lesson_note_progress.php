<?php
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');

// Get teacher's classes and subjects
$teacher_id = $this->session->userdata('login_user_id');
$teacher_classes = $this->db->query("
    SELECT DISTINCT c.class_id, c.name, c.name_numeric
    FROM class c
    JOIN subject s ON s.class_id = c.class_id
    WHERE s.teacher_id = ?
    ORDER BY c.name, c.name_numeric
", [$teacher_id])->result_array();

$teacher_subjects = $this->db->query("
    SELECT s.subject_id, s.name, c.class_id, c.name as class_name, c.name_numeric
    FROM subject s
    JOIN class c ON c.class_id = s.class_id
    WHERE s.teacher_id = ?
    ORDER BY c.name, c.name_numeric, s.name
", [$teacher_id])->result_array();

// Get progress data
$progress_data = $this->Lesson_note_model->get_completion_progress($teacher_id, $running_term, $running_year);
$missing_notes = $this->Lesson_note_model->get_missing_lesson_notes($teacher_id, $running_term, $running_year);

// Extract data from progress array
$total_expected = $progress_data['total_expected'] ?? 0;
$total_submitted = $progress_data['submitted'] ?? 0;
$total_approved = $progress_data['approved'] ?? 0;
$total_pending = $progress_data['pending'] ?? 0;
$total_declined = $progress_data['declined'] ?? 0;

$completion_rate = $total_expected > 0 ? round(($total_approved / $total_expected) * 100, 1) : 0;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-chart-line"></i> <?php echo get_phrase('lesson_note_progress'); ?>
            <small><?php echo get_phrase('track_your_submissions'); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo site_url('teacher/dashboard'); ?>"><i class="fa fa-dashboard"></i> <?php echo get_phrase('dashboard'); ?></a></li>
            <li class="active"><?php echo get_phrase('lesson_note_progress'); ?></li>
        </ol>
    </section>

    <section class="content">
        <!-- Term Filter -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-filter"></i> <?php echo get_phrase('filter_by_term'); ?></h3>
                    </div>
                    <div class="box-body">
                        <?php echo form_open('', array('id' => 'term-filter-form', 'class' => 'form-inline', 'method' => 'GET')); ?>
                            <div class="form-group">
                                <label><?php echo get_phrase('term'); ?>:</label>
                                <select name="term" id="term-select" class="form-control">
                                    <option value="1" <?php echo $running_term == 1 ? 'selected' : ''; ?>><?php echo get_phrase('term'); ?> 1</option>
                                    <option value="2" <?php echo $running_term == 2 ? 'selected' : ''; ?>><?php echo get_phrase('term'); ?> 2</option>
                                    <option value="3" <?php echo $running_term == 3 ? 'selected' : ''; ?>><?php echo get_phrase('term'); ?> 3</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo get_phrase('year'); ?>:</label>
                                <select name="year" id="year-select" class="form-control">
                                    <option value="<?php echo $running_year; ?>" selected><?php echo $running_year; ?></option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> <?php echo get_phrase('apply_filter'); ?>
                            </button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Progress Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-tasks"></i> <?php echo get_phrase('overall_completion_progress'); ?></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="progress-container">
                                    <div class="progress-label">
                                        <span><?php echo get_phrase('completion_rate'); ?></span>
                                        <span class="progress-value"><?php echo $completion_rate; ?>%</span>
                                    </div>
                                    <div class="progress progress-lg">
                                        <div class="progress-bar progress-bar-success progress-bar-striped active" 
                                             role="progressbar" 
                                             aria-valuenow="<?php echo $completion_rate; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100" 
                                             style="width: <?php echo $completion_rate; ?>%">
                                            <span class="sr-only"><?php echo $completion_rate; ?>% Complete</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-icon bg-blue">
                                            <i class="fa fa-file-text"></i>
                                        </div>
                                        <div class="stat-info">
                                            <span class="stat-number"><?php echo $total_expected; ?></span>
                                            <span class="stat-label"><?php echo get_phrase('expected_notes'); ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon bg-green">
                                            <i class="fa fa-check-circle"></i>
                                        </div>
                                        <div class="stat-info">
                                            <span class="stat-number"><?php echo $total_approved; ?></span>
                                            <span class="stat-label"><?php echo get_phrase('approved'); ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon bg-yellow">
                                            <i class="fa fa-clock-o"></i>
                                        </div>
                                        <div class="stat-info">
                                            <span class="stat-number"><?php echo $total_pending; ?></span>
                                            <span class="stat-label"><?php echo get_phrase('pending'); ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon bg-red">
                                            <i class="fa fa-times-circle"></i>
                                        </div>
                                        <div class="stat-info">
                                            <span class="stat-number"><?php echo $total_declined; ?></span>
                                            <span class="stat-label"><?php echo get_phrase('declined'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <canvas id="statusChart" style="height: 250px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress by Subject and Class -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-book"></i> <?php echo get_phrase('progress_by_subject_and_class'); ?></h3>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th><?php echo get_phrase('class'); ?></th>
                                        <th><?php echo get_phrase('subject'); ?></th>
                                        <th><?php echo get_phrase('expected'); ?></th>
                                        <th><?php echo get_phrase('submitted'); ?></th>
                                        <th><?php echo get_phrase('approved'); ?></th>
                                        <th><?php echo get_phrase('pending'); ?></th>
                                        <th><?php echo get_phrase('declined'); ?></th>
                                        <th><?php echo get_phrase('progress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($progress_data['by_subject'])): ?>
                                        <?php foreach ($progress_data['by_subject'] as $subject_id => $row): ?>
                                            <?php 
                                            $row_progress = $row['expected'] > 0 ? round(($row['approved'] / $row['expected']) * 100, 1) : 0;
                                            $progress_class = $row_progress >= 80 ? 'success' : ($row_progress >= 50 ? 'warning' : 'danger');
                                            ?>
                                            <tr>
                                                <td><?php echo $row['class_name']; ?></td>
                                                <td><?php echo $row['subject_name']; ?></td>
                                                <td><?php echo $row['expected']; ?></td>
                                                <td><?php echo $row['submitted']; ?></td>
                                                <td><span class="label label-success"><?php echo $row['approved']; ?></span></td>
                                                <td><span class="label label-warning"><?php echo $progress_data['pending']; ?></span></td>
                                                <td><span class="label label-danger"><?php echo $progress_data['declined']; ?></span></td>
                                                <td>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar progress-bar-<?php echo $progress_class; ?>" 
                                                             style="width: <?php echo $row_progress; ?>%">
                                                            <?php echo $row_progress; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                <i class="fa fa-info-circle"></i> <?php echo get_phrase('no_progress_data_available'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Missing Lesson Notes -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> <?php echo get_phrase('missing_lesson_notes'); ?></h3>
                        <div class="box-tools pull-right">
                            <span class="label label-danger"><?php echo count($missing_notes); ?> <?php echo get_phrase('missing'); ?></span>
                        </div>
                    </div>
                    <div class="box-body">
                        <?php if (!empty($missing_notes)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo get_phrase('week'); ?></th>
                                            <th><?php echo get_phrase('class'); ?></th>
                                            <th><?php echo get_phrase('subject'); ?></th>
                                            <th><?php echo get_phrase('action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($missing_notes as $missing): ?>
                                            <tr class="danger">
                                                <td>
                                                    <span class="label label-danger">
                                                        <?php echo get_phrase('week'); ?> <?php echo $missing['week_number']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $missing['class_name'] ?? 'N/A'; ?></td>
                                                <td><?php echo $missing['subject_name']; ?></td>
                                                <td>
                                                    <a href="<?php echo site_url('teacher/lesson_note_create?subject_id=' . $missing['subject_id'] . '&week=' . $missing['week_number']); ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fa fa-plus"></i> <?php echo get_phrase('create_now'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fa fa-check-circle"></i> <?php echo get_phrase('all_lesson_notes_submitted'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Submission Info -->
        <div class="row">
            <div class="col-md-6">
                <div class="info-box bg-aqua">
                    <span class="info-box-icon"><i class="fa fa-calendar-check-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?php echo get_phrase('last_submission'); ?></span>
                        <span class="info-box-number" id="last-submission-date">
                            <?php 
                            $last_submission = $this->Lesson_note_model->get_last_submission($teacher_id);
                            echo $last_submission ? date('d M, Y', strtotime($last_submission)) : get_phrase('no_submissions_yet');
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-thumbs-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?php echo get_phrase('last_approval'); ?></span>
                        <span class="info-box-number" id="last-approval-date">
                            <?php 
                            $last_approval = $this->Lesson_note_model->get_last_approval($teacher_id);
                            echo $last_approval ? date('d M, Y', strtotime($last_approval)) : get_phrase('no_approvals_yet');
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.progress-container {
    margin-bottom: 20px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-weight: 600;
}

.progress-value {
    color: #00a65a;
    font-size: 1.2em;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-right: 15px;
    color: white;
    font-size: 1.5em;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 1.5em;
    font-weight: bold;
}

.stat-label {
    color: #777;
    font-size: 0.9em;
}

.bg-blue { background-color: #3c8dbc; }
.bg-green { background-color: #00a65a; }
.bg-yellow { background-color: #f39c12; }
.bg-red { background-color: #dd4b39; }

.chart-container {
    position: relative;
    height: 250px;
}
</style>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
$(document).ready(function() {
    // Status Chart
    var ctx = document.getElementById('statusChart').getContext('2d');
    var statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                '<?php echo get_phrase("approved"); ?>',
                '<?php echo get_phrase("pending"); ?>',
                '<?php echo get_phrase("declined"); ?>',
                '<?php echo get_phrase("not_submitted"); ?>'
            ],
            datasets: [{
                data: [
                    <?php echo $total_approved; ?>,
                    <?php echo $total_pending; ?>,
                    <?php echo $total_declined; ?>,
                    <?php echo max(0, $total_expected - $total_submitted); ?>
                ],
                backgroundColor: [
                    '#00a65a',
                    '#f39c12',
                    '#dd4b39',
                    '#d2d6de'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Term filter form
    $('#term-filter-form').on('submit', function(e) {
        e.preventDefault();
        var term = $('#term-select').val();
        var year = $('#year-select').val();
        window.location.href = '<?php echo site_url('teacher/lesson_note_progress'); ?>?term=' + term + '&year=' + year;
    });
});
</script>
