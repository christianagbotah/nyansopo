<!doctype html>
<html>
<head>
    <title><?= $page_title; ?></title>
    <?php include(VIEWPATH . 'backend/includes_top.php'); ?>
    
    <!-- Modern CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/sync-design-system.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/attendance-report-modern.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/css/buttons.dataTables.css">
    
    <!-- Date Range Picker -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/css/daterangepicker.css">
    
    <!-- Chart.js -->
    <script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
</head>

<body>
    <div class="attendance-report-container">
        <!-- Page Header -->
        <div class="attendance-report-header">
            <div class="attendance-report-header-content">
                <h1>
                    <i class="fa fa-chart-bar"></i>
                    Student Attendance Report
                </h1>
                <p>Generate comprehensive attendance reports with advanced filtering options</p>
            </div>
            
            <div class="attendance-report-header-actions">
                <button class="sync-btn sync-btn-secondary" id="refresh-btn">
                    <i class="fa fa-sync-alt"></i> Refresh
                </button>
                <button class="sync-btn sync-btn-success" id="export-excel-btn">
                    <i class="fa fa-file-excel"></i> Export Excel
                </button>
                <button class="sync-btn sync-btn-danger" id="export-pdf-btn">
                    <i class="fa fa-file-pdf"></i> Export PDF
                </button>
                <button class="sync-btn sync-btn-primary" id="print-btn">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>
        </div>

        <!-- Summary Statistics Cards -->
        <div class="attendance-summary-cards" id="summary-cards">
            <div class="attendance-summary-card card-present" data-attendance-type="present" style="cursor: pointer;" title="Click to view details">
                <div class="card-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Present</div>
                    <div class="card-value" id="present-count">0</div>
                    <div class="card-percentage" id="present-percentage">0%</div>
                    <div class="card-breakdown" id="present-breakdown" style="font-size: 11px; margin-top: 4px; opacity: 0.9; display: none;">
                        <div><strong>Present:</strong> <span id="present-regular-count">0</span></div>
                        <div><strong>Late:</strong> <span id="present-late-count">0</span></div>
                    </div>
                </div>
            </div>
            
            <div class="attendance-summary-card card-absent" data-attendance-type="absent" style="cursor: pointer;" title="Click to view details">
                <div class="card-icon">
                    <i class="fa fa-times-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Absent</div>
                    <div class="card-value" id="absent-count">0</div>
                    <div class="card-percentage" id="absent-percentage">0%</div>
                    <div class="card-breakdown" id="absent-breakdown" style="font-size: 11px; margin-top: 4px; opacity: 0.9; display: none;">
                        <div><strong>Absent:</strong> <span id="absent-regular-count">0</span></div>
                        <div><strong>Sick - Home:</strong> <span id="absent-sick-home-count">0</span></div>
                        <div><strong>Sick - Clinic:</strong> <span id="absent-sick-clinic-count">0</span></div>
                        <div><strong>Excused:</strong> <span id="absent-excused-count">0</span></div>
                    </div>
                </div>
            </div>
            
            <div class="attendance-summary-card card-not-marked" data-attendance-type="not_marked" style="cursor: pointer;" title="Click to view details">
                <div class="card-icon">
                    <i class="fa fa-question-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Not Marked</div>
                    <div class="card-value" id="not-marked-count">0</div>
                    <div class="card-percentage" id="not-marked-percentage">0%</div>
                </div>
            </div>
            
            <div class="attendance-summary-card card-total" data-attendance-type="total" style="cursor: pointer;" title="Click to view details">
                <div class="card-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Total Students</div>
                    <div class="card-value" id="total-count">0</div>
                    <div class="card-percentage">100%</div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="attendance-filters-card">
            <div class="filters-header">
                <h3><i class="fa fa-filter"></i> Filter Options</h3>
                
                <div class="filters-header-search">
                    <input type="text" id="search-student" class="filter-input-header" placeholder="Search by name, ID, or parent...">
                </div>
                
                <button class="sync-btn sync-btn-sm sync-btn-secondary" id="reset-filters-btn">
                    <i class="fa fa-undo"></i> Reset Filters
                </button>
            </div>
            
            <!-- Teacher Information Display (shown when class is selected) -->
            <div id="teacher-info-section" class="teacher-info-section" style="display: none;">
                <div class="teacher-info-card">
                    <div class="teacher-info-icon">
                        <i class="fa fa-chalkboard-teacher"></i>
                    </div>
                    <div class="teacher-info-content">
                        <div class="teacher-info-label">Class Teacher</div>
                        <div class="teacher-info-name" id="teacher-name">-</div>
                        <div class="teacher-info-contact">
                            <i class="fa fa-phone"></i>
                            <span id="teacher-phone">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="filters-single-row">
                <!-- Date Range Filter -->
                <div class="filter-group">
                    <label for="date-range">
                        <i class="fa fa-calendar"></i> Date Range
                    </label>
                    <input type="text" id="date-range" class="filter-input" placeholder="Select date range">
                </div>
                
                <!-- Attendance Status Filter -->
                <div class="filter-group">
                    <label for="filter-status">
                        <i class="fa fa-check-square"></i> Status
                    </label>
                    <select id="filter-status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="1">Present</option>
                        <option value="2">Absent</option>
                        <option value="0">Not Marked</option>
                    </select>
                </div>
                
                <!-- Class Filter -->
                <div class="filter-group">
                    <label for="filter-class">
                        <i class="fa fa-school"></i> Class
                    </label>
                    <select id="filter-class" class="filter-select">
                        <option value="">All Classes</option>
                        <?php getFullClassList(); ?>
                    </select>
                </div>
                
                <!-- Gender Filter -->
                <div class="filter-group">
                    <label for="filter-gender">
                        <i class="fa fa-venus-mars"></i> Gender
                    </label>
                    <select id="filter-gender" class="filter-select">
                        <option value="">All Genders</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                
                <!-- Residential Status Filter (if boarding system is enabled) -->
                <?php
                $boarding_enabled = $this->db->get_where('settings', array('type' => 'boarding_enabled'))->row();
                if ($boarding_enabled && $boarding_enabled->description == '1'):
                ?>
                <div class="filter-group">
                    <label for="filter-residential">
                        <i class="fa fa-home"></i> Residential
                    </label>
                    <select id="filter-residential" class="filter-select">
                        <option value="">All Students</option>
                        <option value="boarding">Boarding</option>
                        <option value="day">Day</option>
                    </select>
                </div>
                
                <!-- Boarding House Filter -->
                <div class="filter-group">
                    <label for="filter-house">
                        <i class="fa fa-building"></i> House
                    </label>
                    <select id="filter-house" class="filter-select">
                        <option value="">All Houses</option>
                        <?php
                        $houses = $this->db->get('boarding_house')->result_array();
                        foreach ($houses as $house):
                        ?>
                            <option value="<?php echo $house['house_id']; ?>">
                                <?php echo $house['house_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Apply Filters Button -->
                <div class="filter-group filter-actions">
                    <button class="sync-btn sync-btn-primary btn-block" id="apply-filters-btn">
                        <i class="fa fa-filter"></i> Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table Section -->
        <div class="attendance-table-card">
            <div class="table-header">
                <h3><i class="fa fa-table"></i> Student Attendance Records</h3>
                <div class="table-info">
                    <span id="showing-info">Showing 0 of 0 records</span>
                </div>
            </div>
            
            <div class="table-container" id="table-container">
                <div class="loading-state">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>Loading attendance data...</p>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="attendance-chart-section">
            <div class="chart-card">
                <h3><i class="fa fa-chart-pie"></i> Attendance Distribution</h3>
                <canvas id="attendance-chart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3><i class="fa fa-chart-bar"></i> Class-wise Breakdown</h3>
                <canvas id="class-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loading-modal" class="loading-modal">
        <div class="loading-modal-content">
            <div class="loading-spinner">
                <i class="fa fa-spinner fa-spin fa-4x"></i>
            </div>
            <h3>Loading Attendance Data</h3>
            <p>Please wait while we fetch the attendance records...</p>
        </div>
    </div>

    <?php include VIEWPATH . 'backend/includes_bottom.php'; ?>
    
    <!-- Include Attendance Details Modal -->
    <?php include VIEWPATH . 'backend/admin/modal_attendance_details.php'; ?>
    
    <!-- DataTables JS -->
    <script src="<?php echo base_url(); ?>assets/cdn/js/dataTables.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/dataTables.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/dataTables.buttons-3.0.2.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.dataTables-3.2.4.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/jszip.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/pdfmake.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/vfs_fonts.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.html5.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/buttons.html5.min.js"></script>
    
    <!-- Date Range Picker -->
    <script src="<?php echo base_url(); ?>assets/cdn/js/moment.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/daterangepicker.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo base_url('assets/backend/js/attendance_report_modern.js'); ?>?v=<?php echo time(); ?>"></script>
    
    <!-- Attendance Details Modal JS -->
    <script src="<?php echo base_url('assets/backend/js/attendance_details_modal.js'); ?>?v=<?php echo time(); ?>"></script>
    
    <script>
        // Initialize with timestamp from PHP
        var initialTimestamp = <?php echo $timestamp; ?>;
        var baseUrl = '<?php echo site_url(); ?>';
        var base_url = '<?php echo site_url(); ?>'; // Alternative variable name for compatibility
        var csrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
        var csrfTokenName = '<?php echo $this->security->get_csrf_token_name(); ?>';
        var csrf_token_value = csrfToken; // Alternative variable name
        var csrf_token_name = csrfTokenName; // Alternative variable name
        
        // Initialize the attendance report
        $(document).ready(function() {
            AttendanceReport.init(initialTimestamp);
        });
    </script>
</body>
</html>
