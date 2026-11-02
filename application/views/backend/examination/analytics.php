<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <h3 class="panel-title" style="color: white;">
                    <i class="fa fa-chart-line"></i> Performance Analytics
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <select id="analytics_exam" class="form-control" onchange="loadAnalytics()">
                            <option value="">Select Exam</option>
                            <?php
                            $exams = $this->db->get('enterprise_exams')->result_array();
                            foreach($exams as $exam):
                            ?>
                            <option value="<?php echo $exam['exam_id']; ?>"><?php echo $exam['exam_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row" id="stats-cards" style="display: none;">
                    <div class="col-md-3">
                        <div class="panel" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="panel-body text-center">
                                <h3 id="total_students">0</h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                            <div class="panel-body text-center">
                                <h3 id="pass_rate">0%</h3>
                                <p>Pass Rate</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                            <div class="panel-body text-center">
                                <h3 id="avg_score">0</h3>
                                <p>Average Score</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white;">
                            <div class="panel-body text-center">
                                <h3 id="top_score">0</h3>
                                <p>Highest Score</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row" id="charts-container" style="display: none;">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">Grade Distribution</div>
                            <div class="panel-body">
                                <canvas id="gradeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">Subject Performance</div>
                            <div class="panel-body">
                                <canvas id="subjectChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
let gradeChart, subjectChart;

function loadAnalytics() {
    const examId = $('#analytics_exam').val();
    if(!examId) return;
    
    $.ajax({
        url: '<?php echo site_url("examination/get_analytics"); ?>',
        type: 'GET',
        data: {exam_id: examId},
        dataType: 'json',
        success: function(response) {
            displayStats(response.stats);
            displayCharts(response.charts);
            $('#stats-cards, #charts-container').show();
        }
    });
}

function displayStats(stats) {
    $('#total_students').text(stats.total_students);
    $('#pass_rate').text(stats.pass_rate + '%');
    $('#avg_score').text(stats.avg_score);
    $('#top_score').text(stats.top_score);
}

function displayCharts(data) {
    // Grade Distribution
    if(gradeChart) gradeChart.destroy();
    gradeChart = new Chart($('#gradeChart'), {
        type: 'bar',
        data: {
            labels: data.grades.labels,
            datasets: [{
                label: 'Number of Students',
                data: data.grades.values,
                backgroundColor: 'rgba(102, 126, 234, 0.8)'
            }]
        },
        options: {
            responsive: true,
            scales: {y: {beginAtZero: true}}
        }
    });
    
    // Subject Performance
    if(subjectChart) subjectChart.destroy();
    subjectChart = new Chart($('#subjectChart'), {
        type: 'line',
        data: {
            labels: data.subjects.labels,
            datasets: [{
                label: 'Average Score',
                data: data.subjects.values,
                borderColor: 'rgba(67, 233, 123, 1)',
                backgroundColor: 'rgba(67, 233, 123, 0.2)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {y: {beginAtZero: true, max: 100}}
        }
    });
}
</script>
