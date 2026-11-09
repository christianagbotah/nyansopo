<!-- Promotion Status Checker -->
<style>
    .status-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .status-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-bottom: 3px solid #5a67d8;
    }
    
    .status-card-header h3 {
        color: white !important;
        margin: 0;
        font-size: 22px;
    }
    
    .status-card-body {
        padding: 20px;
    }
    
    .teacher-info-box {
        background: #f7fafc;
        border-left: 4px solid #4299e1;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .teacher-info-item {
        margin-bottom: 10px;
        display: flex;
        align-items: center;
    }
    
    .teacher-info-item i {
        width: 24px;
        color: #4299e1;
        margin-right: 10px;
    }
    
    .students-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .students-table thead {
        background: #edf2f7;
    }
    
    .students-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #2d3748;
        border-bottom: 2px solid #cbd5e0;
    }
    
    .students-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .students-table tbody tr:hover {
        background: #f7fafc;
    }
    
    .badge-count {
        background: #fc8181;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        margin-left: 10px;
    }
    
    .no-data-message {
        text-align: center;
        padding: 40px;
        color: #718096;
        font-size: 16px;
    }
    
    .success-icon {
        color: #48bb78;
        font-size: 48px;
    }
    
    .info-header {
        background: #ebf8ff;
        border-left: 4px solid #3182ce;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: white;
    }
    
    .page-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        color: white;
    }
    
    .no-teacher-info {
        color: #e53e3e;
        font-style: italic;
    }
    
    .student-code {
        font-family: monospace;
        background: #edf2f7;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 13px;
    }
    
    /* Vertical Tabs Styling */
    .tabs-container {
        display: flex;
        gap: 20px;
        min-height: 500px;
        position: relative;
    }
    
    .vertical-tabs {
        flex: 0 0 280px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 15px;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
        position: sticky;
        top: 100px;
        align-self: flex-start;
    }
    
    .vertical-tabs h4 {
        margin: 0 0 15px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
        color: #2d3748;
        font-size: 16px;
    }
    
    .tab-button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 12px 15px;
        margin-bottom: 8px;
        border: none;
        background: #f7fafc;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
        color: #2d3748;
        border-left: 4px solid transparent;
    }
    
    .tab-button:hover {
        background: #edf2f7;
        border-left-color: #667eea;
    }
    
    .tab-button.active {
        background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
        border-left-color: #667eea;
        font-weight: 600;
        color: #667eea;
    }
    
    .tab-button .class-name {
        display: block;
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .tab-button .student-count {
        display: inline-block;
        background: #fc8181;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        margin-left: 5px;
    }
    
    .tab-content-wrapper {
        flex: 1;
        min-width: 0;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (max-width: 768px) {
        .tabs-container {
            flex-direction: column;
        }
        
        .vertical-tabs {
            flex: none;
            position: relative;
            max-height: 300px;
            top: 0;
        }
    }
</style>

<div class="page-header">
    <h2><i class="fa fa-check-square"></i> Promotion Status Checker</h2>
    <p>Review classes with students who haven't been promoted or repeated for the next academic period</p>
    <div style="margin-top: 15px; font-size: 14px;">
        <strong>Current Session:</strong> <?php echo $running_year; ?> | 
        <strong>Term:</strong> <?php echo $running_term; ?>
    </div>
</div>

<?php if (empty($unpromoted_data)): ?>
    <!-- All Students Promoted -->
    <div class="status-card">
        <div class="status-card-body">
            <div class="no-data-message">
                <i class="fa fa-check-circle success-icon"></i>
                <h3 style="margin-top: 20px; color: #2d3748;">All Students Have Been Promoted!</h3>
                <p>There are no pending promotions or repetitions for the current session.</p>
                <p style="margin-top: 10px; color: #718096;">
                    <small>All students in <?php echo $running_year; ?> Term <?php echo $running_term; ?> have been enrolled for the next period.</small>
                </p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Summary Info -->
    <div class="info-header">
        <i class="fa fa-info-circle"></i> 
        <strong><?php echo count($unpromoted_data); ?> class(es)</strong> have students pending promotion or repetition.
        <?php if ($last_exam): ?>
            <br><small style="margin-top: 5px; display: inline-block;">
                <i class="fa fa-file-alt"></i> Exam data shown from: <strong><?php echo $last_exam->name; ?></strong> 
                <?php if (isset($last_exam->date) && $last_exam->date): ?>
                    (<?php echo date('d M Y', is_numeric($last_exam->date) ? $last_exam->date : strtotime($last_exam->date)); ?>)
                <?php endif; ?>
            </small>
        <?php endif; ?>
    </div>
    
    <!-- Vertical Tabs Layout -->
    <div class="tabs-container">
        <!-- Left Sidebar - Class Tabs -->
        <div class="vertical-tabs">
            <h4><i class="fa fa-list"></i> Classes</h4>
            <?php $tab_index = 0; ?>
            <?php foreach ($unpromoted_data as $data): ?>
                <?php 
                    // Get section name
                    $section_name = '';
                    if (isset($data['class']['class_id'])) {
                        $section_row = $this->db->get_where('section', array('class_id' => $data['class']['class_id']))->row();
                        if ($section_row) {
                            $section_name = $section_row->name;
                        }
                    }
                    
                    $full_class_name = strtoupper($data['class']['name']);
                    if (!empty($data['class']['name_numeric'])) {
                        $full_class_name .= ' ' . $data['class']['name_numeric'];
                    }
                    if (!empty($section_name)) {
                        $full_class_name .= ' ' . $section_name;
                    }
                ?>
                <button class="tab-button <?php echo $tab_index == 0 ? 'active' : ''; ?>" 
                        onclick="switchTab(<?php echo $tab_index; ?>)" 
                        id="tab-btn-<?php echo $tab_index; ?>">
                    <span class="class-name"><?php echo $full_class_name; ?></span>
                    <span class="student-count"><?php echo $data['count']; ?> student<?php echo $data['count'] > 1 ? 's' : ''; ?></span>
                </button>
                <?php $tab_index++; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Right Content Area - Tab Contents -->
        <div class="tab-content-wrapper">
            <?php $content_index = 0; ?>
            <?php foreach ($unpromoted_data as $data): ?>
                <?php 
                    // Get section name
                    $section_name = '';
                    if (isset($data['class']['class_id'])) {
                        $section_row = $this->db->get_where('section', array('class_id' => $data['class']['class_id']))->row();
                        if ($section_row) {
                            $section_name = $section_row->name;
                        }
                    }
                    
                    $full_class_name = strtoupper($data['class']['name']);
                    if (!empty($data['class']['name_numeric'])) {
                        $full_class_name .= ' ' . $data['class']['name_numeric'];
                    }
                    if (!empty($section_name)) {
                        $full_class_name .= ' ' . $section_name;
                    }
                ?>
                <div class="tab-content <?php echo $content_index == 0 ? 'active' : ''; ?>" id="tab-content-<?php echo $content_index; ?>">
                    <div class="status-card">
                        <div class="status-card-header">
                            <h3>
                                <i class="fa fa-graduation-cap"></i> 
                                <?php echo $full_class_name; ?>
                                <span class="badge-count"><?php echo $data['count']; ?> Student<?php echo $data['count'] > 1 ? 's' : ''; ?></span>
                            </h3>
                        </div>
                        
                        <div class="status-card-body">
                            <!-- Class Teacher Information -->
                            <h4 style="margin-top: 0; color: #2d3748; margin-bottom: 15px;">
                                <i class="fa fa-user"></i> Class Teacher Details
                            </h4>
                            
                            <?php if (!empty($data['teacher'])): ?>
                                <div class="teacher-info-box">
                                    <div class="teacher-info-item">
                                        <i class="fa fa-user-circle"></i>
                                        <strong>Name:</strong>&nbsp; <?php echo $data['teacher']['name']; ?>
                                    </div>
                                    <div class="teacher-info-item">
                                        <i class="fa fa-envelope"></i>
                                        <strong>Email:</strong>&nbsp; 
                                        <a href="mailto:<?php echo $data['teacher']['email']; ?>" style="color: #4299e1;">
                                            <?php echo $data['teacher']['email'] ?: 'Not provided'; ?>
                                        </a>
                                    </div>
                                    <div class="teacher-info-item">
                                        <i class="fa fa-phone"></i>
                                        <strong>Phone:</strong>&nbsp; 
                                        <?php if ($data['teacher']['phone']): ?>
                                            <a href="tel:<?php echo $data['teacher']['phone']; ?>" style="color: #4299e1;">
                                                <?php echo $data['teacher']['phone']; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="no-teacher-info">Not provided</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="teacher-info-box">
                                    <p class="no-teacher-info" style="margin: 0;">
                                        <i class="fa fa-exclamation-triangle"></i> No class teacher assigned to this class
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Unpromoted Students List -->
                            <h4 style="margin-top: 25px; color: #2d3748; margin-bottom: 15px;">
                                <i class="fa fa-users"></i> Students Pending Promotion/Repetition
                            </h4>
                            
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">Student Code</th>
                                        <th width="20%">Student Name</th>
                                        <th width="12%">Subjects (Written/Total)</th>
                                        <th width="12%">Average %</th>
                                        <th width="36%">Principal's Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; ?>
                                    <?php foreach ($data['students'] as $student): ?>
                                        <?php 
                                        $exam_stats = isset($student['exam_stats']) ? $student['exam_stats'] : array(
                                            'total_subjects' => 0,
                                            'subjects_written' => 0,
                                            'average_percentage' => 0,
                                            'principal_remark' => 'N/A'
                                        );
                                        
                                        // Color code based on average
                                        $avg_color = '#2d3748'; // default
                                        if ($exam_stats['average_percentage'] >= 70) {
                                            $avg_color = '#48bb78'; // green
                                        } elseif ($exam_stats['average_percentage'] >= 50) {
                                            $avg_color = '#ed8936'; // orange
                                        } elseif ($exam_stats['average_percentage'] > 0) {
                                            $avg_color = '#f56565'; // red
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td>
                                                <span class="student-code"><?php echo $student['student_code']; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo $student['name']; ?></strong>
                                            </td>
                                            <td style="text-align: center;">
                                                <span style="font-weight: 600; color: <?php echo $exam_stats['subjects_written'] == $exam_stats['total_subjects'] ? '#48bb78' : '#ed8936'; ?>">
                                                    <?php echo $exam_stats['subjects_written']; ?>
                                                </span>
                                                <span style="color: #718096;"> / </span>
                                                <span style="color: #4a5568;">
                                                    <?php echo $exam_stats['total_subjects']; ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <strong style="color: <?php echo $avg_color; ?>; font-size: 16px;">
                                                    <?php echo number_format($exam_stats['average_percentage'], 1); ?>%
                                                </strong>
                                            </td>
                                            <td>
                                                <em style="color: #4a5568;">
                                                    <?php echo $exam_stats['principal_remark']; ?>
                                                </em>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php $content_index++; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Action Notice -->
    <div style="background: #fff5f5; border: 1px solid #fc8181; border-radius: 8px; padding: 20px; margin-top: 20px;">
        <h4 style="margin-top: 0; color: #c53030;">
            <i class="fa fa-exclamation-circle"></i> Action Required
        </h4>
        <p style="margin-bottom: 10px; color: #742a2a;">
            To complete the promotion process for these students, please visit the 
            <strong>Student Promotion</strong> page and promote or repeat each student as appropriate.
        </p>
        <a href="<?php echo site_url('admin/student_promotion'); ?>" class="btn btn-primary" style="margin-top: 10px;">
            <i class="fa fa-arrow-right"></i> Go to Student Promotion
        </a>
    </div>
<?php endif; ?>

<script>
    function switchTab(index) {
        // Hide all tab contents
        const allContents = document.querySelectorAll('.tab-content');
        allContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Remove active class from all buttons
        const allButtons = document.querySelectorAll('.tab-button');
        allButtons.forEach(button => {
            button.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById('tab-content-' + index).classList.add('active');
        
        // Activate selected button
        document.getElementById('tab-btn-' + index).classList.add('active');
        
        // Scroll to top of content
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    $(document).ready(function() {
        // Add smooth scrolling for better UX
        $('a[href^="#"]').on('click', function(event) {
            var target = $(this.getAttribute('href'));
            if(target.length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });
    });
</script>
