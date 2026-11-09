<?php
$currency_symbol = get_settings('currency');
$stats = $statistics;
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-chart-bar"></i> <?php echo get_phrase('credit_statistics'); ?>
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Statistics Cards -->
                <div class="row">
                    <!-- Total Active Credits -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="panel panel-success">
                            <div class="panel-body" style="padding: 20px; text-align: center;">
                                <i class="fa fa-wallet fa-3x" style="color: #28a745; opacity: 0.3;"></i>
                                <h2 style="margin: 10px 0; font-weight: bold; color: #28a745;">
                                    <?php echo $currency_symbol . ' ' . number_format($stats['total_active_credits'], 2); ?>
                                </h2>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <?php echo get_phrase('total_active_credits'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Credits Created -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="panel panel-info">
                            <div class="panel-body" style="padding: 20px; text-align: center;">
                                <i class="fa fa-plus-circle fa-3x" style="color: #17a2b8; opacity: 0.3;"></i>
                                <h2 style="margin: 10px 0; font-weight: bold; color: #17a2b8;">
                                    <?php echo $currency_symbol . ' ' . number_format($stats['total_credits_created'], 2); ?>
                                </h2>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <?php echo get_phrase('total_credits_created'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Credits Applied -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="panel panel-warning">
                            <div class="panel-body" style="padding: 20px; text-align: center;">
                                <i class="fa fa-check-circle fa-3x" style="color: #ffc107; opacity: 0.3;"></i>
                                <h2 style="margin: 10px 0; font-weight: bold; color: #f39c12;">
                                    <?php echo $currency_symbol . ' ' . number_format($stats['total_credits_applied'], 2); ?>
                                </h2>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <?php echo get_phrase('total_credits_applied'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Students with Credits -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="panel panel-primary">
                            <div class="panel-body" style="padding: 20px; text-align: center;">
                                <i class="fa fa-users fa-3x" style="color: #007bff; opacity: 0.3;"></i>
                                <h2 style="margin: 10px 0; font-weight: bold; color: #007bff;">
                                    <?php echo number_format($stats['students_with_credits']); ?>
                                </h2>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <?php echo get_phrase('students_with_credits'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Utilization Chart -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-chart-pie"></i> <?php echo get_phrase('credit_utilization_rate'); ?>
                                </h4>
                            </div>
                            <div class="panel-body" style="text-align: center; padding: 30px;">
                                <canvas id="utilizationChart" style="max-width: 300px; margin: 0 auto;"></canvas>
                                <h3 style="margin-top: 20px; font-weight: bold; color: #333;">
                                    <?php echo number_format($stats['utilization_rate'], 1); ?>%
                                </h3>
                                <p style="color: #666;">
                                    <?php echo get_phrase('of_credits_have_been_used'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-chart-bar"></i> <?php echo get_phrase('credit_breakdown'); ?>
                                </h4>
                            </div>
                            <div class="panel-body" style="padding: 30px;">
                                <canvas id="breakdownChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-bolt"></i> <?php echo get_phrase('quick_actions'); ?>
                                </h4>
                            </div>
                            <div class="panel-body" style="text-align: center; padding: 30px;">
                                <a href="<?php echo site_url('admin/student_credits'); ?>" class="btn btn-primary btn-lg" style="margin: 5px;">
                                    <i class="fa fa-list"></i> <?php echo get_phrase('manage_credits'); ?>
                                </a>
                                <a href="<?php echo site_url('admin/student_credits'); ?>" class="btn btn-success btn-lg" style="margin: 5px;">
                                    <i class="fa fa-plus"></i> <?php echo get_phrase('add_new_credit'); ?>
                                </a>
                                <a href="<?php echo site_url('admin/student_ledger'); ?>" class="btn btn-info btn-lg" style="margin: 5px;">
                                    <i class="fa fa-book"></i> <?php echo get_phrase('view_ledger'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
$(document).ready(function() {
    // Utilization Doughnut Chart
    var utilizationCtx = document.getElementById('utilizationChart').getContext('2d');
    var utilizationRate = <?php echo $stats['utilization_rate']; ?>;
    var remainingRate = 100 - utilizationRate;
    
    new Chart(utilizationCtx, {
        type: 'doughnut',
        data: {
            labels: ['<?php echo get_phrase('used'); ?>', '<?php echo get_phrase('remaining'); ?>'],
            datasets: [{
                data: [utilizationRate, remainingRate],
                backgroundColor: ['#28a745', '#e0e0e0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });

    // Credit Breakdown Bar Chart
    var breakdownCtx = document.getElementById('breakdownChart').getContext('2d');
    
    new Chart(breakdownCtx, {
        type: 'bar',
        data: {
            labels: [
                '<?php echo get_phrase('active_credits'); ?>',
                '<?php echo get_phrase('applied_credits'); ?>',
                '<?php echo get_phrase('pending_credits'); ?>'
            ],
            datasets: [{
                label: '<?php echo get_phrase('amount'); ?> (<?php echo $currency_symbol; ?>)',
                data: [
                    <?php echo $stats['total_active_credits']; ?>,
                    <?php echo $stats['total_credits_applied']; ?>,
                    <?php echo $stats['total_credits_created'] - $stats['total_credits_applied'] - $stats['total_active_credits']; ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?php echo $currency_symbol; ?>' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
