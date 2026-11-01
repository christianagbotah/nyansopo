<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulk Terminal Reports</title>
    <style>
        @media print {
            @page { 
                margin: 0.5cm;
                size: A4 portrait;
            }
            body { margin: 0; }
            .no-print { display: none; }
            .report-container { page-break-after: always; }
            .report-container:last-child { page-break-after: auto; }
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            margin: 0;
            background: white;
        }
        
        .report-container {
            max-width: 210mm;
            margin: 0 auto 20px;
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
            margin-bottom: 4px;
        }
        
        .school-info {
            font-size: 9pt;
            margin-bottom: 2px;
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
        }
        
        .info-row {
            display: flex;
            margin-bottom: 4px;
        }
        
        .info-label {
            font-weight: bold;
            width: 140px;
        }
        
        .info-value {
            flex: 1;
            border-bottom: 1px dotted #000;
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        
        .scores-table th,
        .scores-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 10pt;
        }
        
        .scores-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        .subject-name {
            text-align: left !important;
            padding-left: 8px !important;
        }
        
        .grade-a1 { background: #d4edda; }
        .grade-b { background: #d1ecf1; }
        .grade-c { background: #fff3cd; }
        .grade-d { background: #f8d7da; }
        .grade-f { background: #f5c6cb; }
        
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
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            Print All Reports
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Close
        </button>
        <div style="margin-top: 10px; font-size: 12px;">
            <strong>Total Reports:</strong> <span id="reportCount">0</span>
        </div>
    </div>

    <?php
    $class_id = $this->uri->segment(3);
    $year = $this->uri->segment(4);
    $term = $this->uri->segment(5);
    
    // Get all students in class
    $students = $this->db->select('s.*, c.name as class_name')
        ->from('student s')
        ->join('class c', 'c.class_id = s.class_id')
        ->where('s.class_id', $class_id)
        ->order_by('s.name', 'ASC')
        ->get()->result();
    
    $school = $this->db->get_where('settings', ['type' => 'system_name'])->row();
    
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
    
    $report_count = 0;
    foreach($students as $student):
        $report_count++;
        
        // Get subjects with scores
        $subjects = $this->db->select('
            s.name as subject_name,
            COALESCE(sba.total_sba, 0) as class_score,
            COALESCE(em.total_score, 0) as exam_score,
            (COALESCE(sba.total_sba, 0) + COALESCE(em.total_score, 0)) as total_score
        ')
        ->from('class_subject cs')
        ->join('subject s', 's.subject_id = cs.subject_id')
        ->join('sba_components sba', 'sba.subject_id = cs.subject_id AND sba.student_id = ' . $student->student_id . ' AND sba.year = "' . $year . '" AND sba.term = "' . $term . '"', 'left')
        ->join('exam_marks em', 'em.subject_id = cs.subject_id AND em.student_id = ' . $student->student_id . ' AND em.year = "' . $year . '" AND em.term = "' . $term . '"', 'left')
        ->where('cs.class_id', $student->class_id)
        ->get()->result();
        
        $report = $this->db->get_where('terminal_reports', [
            'student_id' => $student->student_id,
            'year' => $year,
            'term' => $term
        ])->row();
        
        // Calculate totals
        $total_score = 0;
        $subject_count = 0;
        foreach($subjects as $subject) {
            $total_score += $subject->total_score;
            $subject_count++;
        }
        $average = $subject_count > 0 ? $total_score / $subject_count : 0;
    ?>

    <div class="report-container">
        <!-- Header -->
        <div class="header">
            <div class="school-name"><?php echo $school->description; ?></div>
            <div class="school-info">P.O. Box 123, Accra, Ghana</div>
            <div class="school-info">Tel: 0XX XXX XXXX | Email: info@school.edu.gh</div>
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
        </div>

        <!-- Scores Table -->
        <table class="scores-table">
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
                <?php foreach($subjects as $subject): 
                    $grade = getGrade($subject->total_score);
                    $gradeClass = getGradeClass($grade);
                ?>
                <tr>
                    <td class="subject-name"><?php echo $subject->subject_name; ?></td>
                    <td><?php echo number_format($subject->class_score, 1); ?></td>
                    <td><?php echo number_format($subject->exam_score, 1); ?></td>
                    <td><strong><?php echo number_format($subject->total_score, 1); ?></strong></td>
                    <td class="<?php echo $gradeClass; ?>"><strong><?php echo $grade; ?></strong></td>
                    <td><?php 
                        if($subject->total_score >= 75) echo 'Excellent';
                        elseif($subject->total_score >= 60) echo 'Very Good';
                        elseif($subject->total_score >= 50) echo 'Good';
                        elseif($subject->total_score >= 40) echo 'Pass';
                        else echo 'Fail';
                    ?></td>
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

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-grid">
                <div>
                    <strong>Total:</strong> <?php echo number_format($total_score, 1); ?> | 
                    <strong>Average:</strong> <?php echo number_format($average, 1); ?>%
                </div>
                <div>
                    <strong>Attendance:</strong> <?php echo $report->attendance_present ?? 0; ?>/<?php echo $report->attendance_total ?? 0; ?> | 
                    <strong>Conduct:</strong> <?php echo $report->conduct ?? 'N/A'; ?>
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

    <?php endforeach; ?>

    <script>
        document.getElementById('reportCount').textContent = <?php echo $report_count; ?>;
    </script>
</body>
</html>
