<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Terminal Report - Enhanced</title>
    <style>
        @media print {
            @page { margin: 0.5cm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            margin: 20px;
            background: white;
        }
        
        .report-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border: 2px solid #000;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        
        .school-name {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            margin-top: 8px;
            text-decoration: underline;
        }
        
        .student-info {
            margin: 12px 0;
            border: 1px solid #000;
            padding: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
        }
        
        .info-row {
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        
        .info-value {
            flex: 1;
            border-bottom: 1px dotted #000;
        }
        
        /* JHS WAEC Format */
        .jhs-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10pt;
        }
        
        .jhs-table th,
        .jhs-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        
        .jhs-table th {
            background: #e3f2fd;
            font-weight: bold;
        }
        
        .subject-name {
            text-align: left !important;
            padding-left: 8px !important;
        }
        
        /* Primary/Basic Format */
        .basic-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10pt;
        }
        
        .basic-table th,
        .basic-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        
        .basic-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        .grade-a1 { background: #d4edda; }
        .grade-b { background: #d1ecf1; }
        .grade-c { background: #fff3cd; }
        .grade-d { background: #f8d7da; }
        .grade-f { background: #f5c6cb; }
        
        /* Performance Graph */
        .graph-section {
            margin: 15px 0;
            border: 1px solid #000;
            padding: 10px;
        }
        
        .graph-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            font-size: 11pt;
        }
        
        .chart-container {
            height: 200px;
            margin: 10px 0;
        }
        
        .summary-section {
            margin: 10px 0;
            border: 1px solid #000;
            padding: 8px;
            font-size: 10pt;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .remark-box {
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 8px;
            min-height: 50px;
            font-size: 10pt;
        }
        
        .remark-title {
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 4px;
            text-align: center;
            font-size: 9pt;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 8px;
            border-top: 2px solid #000;
            font-size: 8pt;
            font-style: italic;
        }
        
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
    </style>
    <script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
</head>
<body>
    <button onclick="window.print()" class="no-print" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Print Report
    </button>

    <?php
    $student_id = $this->uri->segment(3);
    $year = $this->uri->segment(4);
    $term = $this->uri->segment(5);
    
    $student = $this->db->select('s.*, c.name as class_name, c.category_id')
        ->from('student s')
        ->join('class c', 'c.class_id = s.class_id')
        ->where('s.student_id', $student_id)
        ->get()->row();
    
    $is_jhs = ($student->category_id == 1); // JHS category
    
    // Get current term subjects with scores
    $subjects_current = $this->db->select('
        s.subject_id,
        s.name as subject_name,
        COALESCE(sba.total_sba, 0) as class_score,
        COALESCE(em.total_score, 0) as exam_score,
        (COALESCE(sba.total_sba, 0) + COALESCE(em.total_score, 0)) as total_score
    ')
    ->from('class_subject cs')
    ->join('subject s', 's.subject_id = cs.subject_id')
    ->join('sba_components sba', 'sba.subject_id = cs.subject_id AND sba.student_id = ' . $student_id . ' AND sba.year = "' . $year . '" AND sba.term = "' . $term . '"', 'left')
    ->join('exam_marks em', 'em.subject_id = cs.subject_id AND em.student_id = ' . $student_id . ' AND em.year = "' . $year . '" AND em.term = "' . $term . '"', 'left')
    ->where('cs.class_id', $student->class_id)
    ->get()->result();
    
    // Get previous term scores (if not term 1)
    $subjects_previous = [];
    $show_comparison = false;
    if($term > 1) {
        $prev_term = $term - 1;
        $subjects_previous = $this->db->select('
            s.subject_id,
            (COALESCE(sba.total_sba, 0) + COALESCE(em.total_score, 0)) as total_score
        ')
        ->from('class_subject cs')
        ->join('subject s', 's.subject_id = cs.subject_id')
        ->join('sba_components sba', 'sba.subject_id = cs.subject_id AND sba.student_id = ' . $student_id . ' AND sba.year = "' . $year . '" AND sba.term = "' . $prev_term . '"', 'left')
        ->join('exam_marks em', 'em.subject_id = cs.subject_id AND em.student_id = ' . $student_id . ' AND em.year = "' . $year . '" AND em.term = "' . $prev_term . '"', 'left')
        ->where('cs.class_id', $student->class_id)
        ->get()->result();
        
        // Create lookup array
        $prev_scores = [];
        foreach($subjects_previous as $subj) {
            $prev_scores[$subj->subject_id] = $subj->total_score;
        }
        $show_comparison = count($subjects_previous) > 0;
    }
    
    $report = $this->db->get_where('terminal_reports', [
        'student_id' => $student_id,
        'year' => $year,
        'term' => $term
    ])->row();
    
    $school = $this->db->get_where('settings', ['type' => 'system_name'])->row();
    
    // Calculate totals
    $total_score = 0;
    $subject_count = 0;
    foreach($subjects_current as $subject) {
        $total_score += $subject->total_score;
        $subject_count++;
    }
    $average = $subject_count > 0 ? $total_score / $subject_count : 0;
    
    function getGrade($score) {
        if($score >= 80) return 'A1';
        if($score >= 70) return 'B2';
        if($score >= 65) return 'B3';
        if($score >= 60) return 'C4';
        if($score >= 55) return 'C5';
        if($score >= 50) return 'C6';
        if($score >= 45) return 'D7';
        if($score >= 40) return 'E8';
        return 'F9';
    }
    
    function getGradeClass($grade) {
        if($grade == 'A1') return 'grade-a1';
        if(in_array($grade, ['B2', 'B3'])) return 'grade-b';
        if(in_array($grade, ['C4', 'C5', 'C6'])) return 'grade-c';
        if(in_array($grade, ['D7', 'E8'])) return 'grade-d';
        return 'grade-f';
    }
    
    function getRemark($score) {
        if($score >= 75) return 'Excellent';
        if($score >= 60) return 'Very Good';
        if($score >= 50) return 'Good';
        if($score >= 40) return 'Pass';
        return 'Fail';
    }
    ?>

    <div class="report-container">
        <!-- Header -->
        <div class="header">
            <div class="school-name"><?php echo $school->description; ?></div>
            <div style="font-size: 9pt; margin-top: 3px;">P.O. Box 123, Accra, Ghana | Tel: 0XX XXX XXXX</div>
            <div class="report-title">TERMINAL REPORT CARD</div>
            <div style="margin-top: 4px; font-size: 10pt;">Academic Year: <?php echo $year; ?> | Term: <?php echo $term; ?></div>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <div class="info-row">
                <div class="info-label">Student Name:</div>
                <div class="info-value"><?php echo strtoupper($student->name); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Class:</div>
                <div class="info-value"><?php echo $student->class_name; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Index Number:</div>
                <div class="info-value"><?php echo $student->student_id; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Term:</div>
                <div class="info-value">Term <?php echo $term; ?></div>
            </div>
        </div>

        <!-- Scores Table (JHS WAEC Format or Basic Format) -->
        <?php if($is_jhs): ?>
        <!-- AUTHENTIC WAEC BECE FORMAT -->
        <table class="jhs-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">CODE</th>
                    <th rowspan="2" style="width: 30%;">SUBJECT</th>
                    <th rowspan="2" style="width: 12%;">RAW SCORE</th>
                    <th rowspan="2" style="width: 12%;">GRADE</th>
                    <th rowspan="2" style="width: 41%;">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subject_codes = [
                    'MATHEMATICS' => '101',
                    'ENGLISH LANGUAGE' => '102',
                    'INTEGRATED SCIENCE' => '103',
                    'SOCIAL STUDIES' => '104',
                    'RELIGIOUS & MORAL EDUCATION' => '105',
                    'GHANAIAN LANGUAGE' => '106',
                    'FRENCH' => '107',
                    'INFORMATION COMMUNICATION TECHNOLOGY' => '108',
                    'CREATIVE ARTS' => '109',
                    'CAREER TECHNOLOGY' => '110',
                    'PHYSICAL EDUCATION' => '111'
                ];
                
                foreach($subjects_current as $subject): 
                    $total = $subject->total_score;
                    $grade = getGrade($total);
                    $gradeClass = getGradeClass($grade);
                    $subject_upper = strtoupper($subject->subject_name);
                    $code = $subject_codes[$subject_upper] ?? '100';
                ?>
                <tr>
                    <td style="text-align: center;"><?php echo $code; ?></td>
                    <td class="subject-name"><?php echo $subject_upper; ?></td>
                    <td style="text-align: center; font-weight: bold;"><?php echo number_format($total, 0); ?></td>
                    <td class="<?php echo $gradeClass; ?>" style="text-align: center; font-weight: bold; font-size: 12pt;"><?php echo $grade; ?></td>
                    <td style="padding-left: 10px;"><?php echo getRemark($total); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- WAEC Grading Key -->
        <div style="margin: 15px 0; padding: 10px; border: 1px solid #000; background: #f9f9f9;">
            <div style="font-weight: bold; margin-bottom: 8px; text-align: center;">GRADING SYSTEM</div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; font-size: 9pt;">
                <div><strong>A1:</strong> 80-100 (Excellent)</div>
                <div><strong>B2:</strong> 70-79 (Very Good)</div>
                <div><strong>B3:</strong> 65-69 (Good)</div>
                <div><strong>C4:</strong> 60-64 (Credit)</div>
                <div><strong>C5:</strong> 55-59 (Credit)</div>
                <div><strong>C6:</strong> 50-54 (Credit)</div>
                <div><strong>D7:</strong> 45-49 (Pass)</div>
                <div><strong>E8:</strong> 40-44 (Pass)</div>
                <div><strong>F9:</strong> 0-39 (Fail)</div>
            </div>
        </div>
        
        <!-- Aggregate Score -->
        <div style="margin: 15px 0; padding: 10px; border: 2px solid #000; background: #fff3cd;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <strong>TOTAL RAW SCORE:</strong> <?php echo number_format($total_score, 0); ?>
                </div>
                <div>
                    <strong>AGGREGATE:</strong> <?php 
                        $aggregate = 0;
                        foreach($subjects_current as $s) {
                            $g = getGrade($s->total_score);
                            $aggregate += (int)substr($g, -1);
                        }
                        echo $aggregate;
                    ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Basic/Primary Format -->
        <table class="basic-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30%;">SUBJECT</th>
                    <th colspan="2">SCORES</th>
                    <th rowspan="2" style="width: 12%;">TOTAL<br>(100)</th>
                    <th rowspan="2" style="width: 10%;">GRADE</th>
                    <th rowspan="2" style="width: 15%;">REMARK</th>
                </tr>
                <tr>
                    <th style="width: 12%;">CLASS<br>(30)</th>
                    <th style="width: 12%;">EXAM<br>(70)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($subjects_current as $subject): 
                    $grade = getGrade($subject->total_score);
                    $gradeClass = getGradeClass($grade);
                ?>
                <tr>
                    <td class="subject-name"><?php echo $subject->subject_name; ?></td>
                    <td><?php echo number_format($subject->class_score, 1); ?></td>
                    <td><?php echo number_format($subject->exam_score, 1); ?></td>
                    <td><strong><?php echo number_format($subject->total_score, 1); ?></strong></td>
                    <td class="<?php echo $gradeClass; ?>"><strong><?php echo $grade; ?></strong></td>
                    <td><?php echo getRemark($subject->total_score); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #e9ecef; font-weight: bold;">
                    <td class="subject-name">TOTAL</td>
                    <td colspan="2"></td>
                    <td><?php echo number_format($total_score, 1); ?></td>
                    <td colspan="2">AVG: <?php echo number_format($average, 1); ?></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Performance Comparison Graph (ONLY for Basic/Primary, NOT JHS) -->
        <?php if($show_comparison && !$is_jhs): ?>
        <div class="graph-section">
            <div class="graph-title">📊 PERFORMANCE COMPARISON: Term <?php echo $term - 1; ?> vs Term <?php echo $term; ?></div>
            <div class="chart-container">
                <canvas id="comparisonChart"></canvas>
            </div>
            <div style="text-align: center; font-size: 9pt; margin-top: 5px; font-style: italic;">
                Blue = Previous Term | Red = Current Term | Higher is Better
            </div>
        </div>
        <?php endif; ?>

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-grid">
                <div>
                    <strong>Total Score:</strong> <?php echo number_format($total_score, 1); ?><br>
                    <strong>Average:</strong> <?php echo number_format($average, 1); ?>%<br>
                    <strong>Position:</strong> _____
                </div>
                <div>
                    <strong>Attendance:</strong> <?php echo $report->attendance_present ?? 0; ?>/<?php echo $report->attendance_total ?? 0; ?><br>
                    <strong>Conduct:</strong> <?php echo $report->conduct ?? 'N/A'; ?><br>
                    <strong>Attitude:</strong> <?php echo $report->attitude ?? 'N/A'; ?>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        <div class="remark-box">
            <div class="remark-title">CLASS TEACHER'S REMARK:</div>
            <div><?php echo $report->teacher_remark ?? ''; ?></div>
        </div>
        
        <div class="remark-box">
            <div class="remark-title">HEADMASTER'S REMARK:</div>
            <div><?php echo $report->headmaster_remark ?? ''; ?></div>
        </div>

        <!-- Signatures -->
        <div class="signature-section">
            <div><div class="signature-line">Class Teacher's Signature</div></div>
            <div><div class="signature-line">Headmaster's Signature & Stamp</div></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <strong>GRADING:</strong> A1 (80-100) | B2 (70-79) | B3 (65-69) | C4-C6 (50-64) | D7-E8 (40-49) | F9 (0-39)
        </div>
    </div>

    <?php if($show_comparison): ?>
    <script>
        const ctx = document.getElementById('comparisonChart').getContext('2d');
        
        const labels = [
            <?php foreach($subjects_current as $subject): ?>
            '<?php echo substr($subject->subject_name, 0, 10); ?>',
            <?php endforeach; ?>
        ];
        
        const currentScores = [
            <?php foreach($subjects_current as $subject): ?>
            <?php echo $subject->total_score; ?>,
            <?php endforeach; ?>
        ];
        
        const previousScores = [
            <?php foreach($subjects_current as $subject): ?>
            <?php echo isset($prev_scores[$subject->subject_id]) ? $prev_scores[$subject->subject_id] : 0; ?>,
            <?php endforeach; ?>
        ];
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Term <?php echo $term - 1; ?>',
                    data: previousScores,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Term <?php echo $term; ?>',
                    data: currentScores,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            font: { size: 10 }
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: 9 }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
