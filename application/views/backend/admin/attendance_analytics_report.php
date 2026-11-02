<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.analytics-container { background: #f8fafc; min-height: 100vh; padding: 24px; max-width: 1600px; margin: 0 auto; }
.analytics-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3); }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
@media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }
.stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.stat-card.green { border-color: #10b981; }
.stat-card.red { border-color: #ef4444; }
.stat-card.orange { border-color: #f59e0b; }
.stat-card.blue { border-color: #3b82f6; }
.stat-value { font-size: 36px; font-weight: 700; margin: 8px 0; }
.stat-label { color: #6b7280; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.chart-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
.chart-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
@media (max-width: 768px) { .chart-grid { grid-template-columns: 1fr; } }
.table-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
.modern-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
.modern-table tr:hover { background: #f9fafb; }
.btn-enterprise { padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-success { background: #10b981; color: white; }
.btn-primary { background: #6366f1; color: white; }
.btn-enterprise:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
@media print {
    .analytics-header .btn-enterprise { display: none !important; }
    .table-card { box-shadow: none; }
}
</style>

<link href="<?php echo base_url(); ?>assets/cdn/css/select2-4.1.0.min.css" rel="stylesheet" />
<script src="<?php echo base_url(); ?>assets/cdn/js/select2-4.1.0.min.js"></script>
<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>

<div class="analytics-container">
    <div class="analytics-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700;">
                    <i class="fa fa-chart-line"></i> <?php echo get_phrase('attendance_analytics'); ?>
                </h1>
                <p style="margin: 0; opacity: 0.9; font-size: 16px;">
                    <?php echo $class_name . ' ' . $class_name_numeric . ' - ' . get_phrase('section') . ' ' . $section_name; ?>
                </p>
                <p style="margin: 4px 0 0 0; opacity: 0.8; font-size: 14px;">
                    <?php echo date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)); ?>
                </p>
                <?php if ($filter_mode == 'per_student' && $student_ids): ?>
                <p style="margin: 4px 0 0 0; opacity: 0.8; font-size: 13px;">
                    <i class="fa fa-filter"></i> <?php echo count($student_ids); ?> <?php echo get_phrase('selected_students'); ?>
                </p>
                <?php endif; ?>
            </div>
            <button class="btn-enterprise btn-success" onclick="window.print()">
                <i class="fa fa-print"></i> <?php echo get_phrase('print'); ?>
            </button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-label"><?php echo get_phrase('school_days'); ?></div>
            <div class="stat-value"><?php echo $school_days; ?></div>
            <div style="color: #6b7280; font-size: 13px;"><?php echo get_phrase('in_date_range'); ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label"><?php echo get_phrase('total_present'); ?></div>
            <div class="stat-value"><?php echo $overall_stats['total_present']; ?></div>
            <div style="color: #6b7280; font-size: 13px;">
                <?php echo $overall_stats['attendance_rate']; ?>% <?php echo get_phrase('rate'); ?>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-label"><?php echo get_phrase('total_absent'); ?></div>
            <div class="stat-value"><?php echo $overall_stats['total_absent']; ?></div>
            <div style="color: #6b7280; font-size: 13px;">
                <?php 
                    $absent_rate = $overall_stats['total_records'] > 0 
                        ? round(($overall_stats['total_absent'] / $overall_stats['total_records']) * 100, 2) 
                        : 0;
                    echo $absent_rate;
                ?>% <?php echo get_phrase('rate'); ?>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label"><?php echo get_phrase('other_statuses'); ?></div>
            <div class="stat-value">
                <?php echo $overall_stats['total_late'] + $overall_stats['total_sick_home'] + $overall_stats['total_sick_clinic']; ?>
            </div>
            <div style="color: #6b7280; font-size: 13px;">
                Late: <?php echo $overall_stats['total_late']; ?>, 
                Sick: <?php echo $overall_stats['total_sick_home'] + $overall_stats['total_sick_clinic']; ?>
            </div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-chart-pie"></i> <?php echo get_phrase('status_breakdown'); ?>
            </h3>
            <canvas id="statusChart" height="250"></canvas>
        </div>
        <div class="chart-card">
            <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-chart-bar"></i> <?php echo get_phrase('attendance_distribution'); ?>
            </h3>
            <canvas id="distributionChart" height="250"></canvas>
        </div>
    </div>

    <div class="table-card">
        <h3 style="margin: 0 0 20px 0; font-size: 20px; font-weight: 700;">
            <i class="fa fa-table"></i> <?php echo get_phrase('student_details'); ?>
        </h3>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><?php echo get_phrase('roll'); ?></th>
                        <th><?php echo get_phrase('student_name'); ?></th>
                        <th><?php echo get_phrase('present'); ?></th>
                        <th><?php echo get_phrase('absent'); ?></th>
                        <th><?php echo get_phrase('late'); ?></th>
                        <th>Sick-Home</th>
                        <th>Sick-Clinic</th>
                        <th><?php echo get_phrase('total'); ?></th>
                        <th><?php echo get_phrase('attendance_rate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($analytics)): ?>
                        <?php foreach ($analytics as $student_id => $data): ?>
                            <tr>
                                <td><?php echo isset($data['student']['roll']) ? $data['student']['roll'] : '-'; ?></td>
                                <td><?php echo $data['student']['name']; ?></td>
                                <td><span class="badge badge-success"><?php echo $data['present']; ?></span></td>
                                <td><span class="badge badge-danger"><?php echo $data['absent']; ?></span></td>
                                <td><span class="badge badge-warning"><?php echo $data['late']; ?></span></td>
                                <td><?php echo $data['sick_home']; ?></td>
                                <td><?php echo $data['sick_clinic']; ?></td>
                                <td><strong><?php echo $data['total_days']; ?></strong></td>
                                <td>
                                    <strong style="color: <?php echo $data['attendance_rate'] >= 80 ? '#10b981' : ($data['attendance_rate'] >= 60 ? '#f59e0b' : '#ef4444'); ?>">
                                        <?php echo $data['attendance_rate']; ?>%
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 24px; color: #6b7280;">
                                <?php echo get_phrase('no_data_available'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Status Breakdown Pie Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: [
            '<?php echo get_phrase('present'); ?>', 
            '<?php echo get_phrase('absent'); ?>', 
            '<?php echo get_phrase('late'); ?>', 
            'Sick-Home', 
            'Sick-Clinic'
        ],
        datasets: [{
            data: [
                <?php echo $overall_stats['total_present']; ?>,
                <?php echo $overall_stats['total_absent']; ?>,
                <?php echo $overall_stats['total_late']; ?>,
                <?php echo $overall_stats['total_sick_home']; ?>,
                <?php echo $overall_stats['total_sick_clinic']; ?>
            ],
            backgroundColor: [
                '#10b981', // Green for present
                '#ef4444', // Red for absent
                '#f59e0b', // Orange for late
                '#8b5cf6', // Purple for sick-home
                '#ec4899'  // Pink for sick-clinic
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Attendance Distribution Bar Chart
const distributionCtx = document.getElementById('distributionChart').getContext('2d');
const distributionChart = new Chart(distributionCtx, {
    type: 'bar',
    data: {
        labels: [
            '0-20%', '21-40%', '41-60%', '61-80%', '81-100%'
        ],
        datasets: [{
            label: '<?php echo get_phrase('number_of_students'); ?>',
            data: [
                <?php
                    $ranges = [0, 0, 0, 0, 0];
                    foreach ($analytics as $data) {
                        $rate = $data['attendance_rate'];
                        if ($rate <= 20) $ranges[0]++;
                        else if ($rate <= 40) $ranges[1]++;
                        else if ($rate <= 60) $ranges[2]++;
                        else if ($rate <= 80) $ranges[3]++;
                        else $ranges[4]++;
                    }
                    echo implode(',', $ranges);
                ?>
            ],
            backgroundColor: [
                '#ef4444', // Red for 0-20%
                '#f59e0b', // Orange for 21-40%
                '#eab308', // Yellow for 41-60%
                '#84cc16', // Lime for 61-80%
                '#10b981'  // Green for 81-100%
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' student' + (context.parsed.y !== 1 ? 's' : '');
                    }
                }
            }
        }
    }
});
</script>
