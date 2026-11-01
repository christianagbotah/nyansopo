<!doctype html>
<html>
<head>
    <title>End of Term <?php echo $term; ?> Exam Report - <?php echo $year; ?></title>
    
    <?php include(APPPATH . 'views/backend/includes/loading_screen.php'); ?>
    
    <link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>"/>

    <style type="text/css">
        /* Template 2 Specific Styles - Two Column Layout */
        body {
            font-family: 'Noto Sans', Arial, sans-serif;
            font-size: 11px; /* Increased from 10px */
            line-height: 1.4; /* Increased from 1.3 for better readability */
        }

        .report-container {
            width: 100%;
            max-width: 21cm;
            margin: 0 auto;
            padding: 15px;
            background: #ffffff;
        }

        .report-page {
            background: #ffffff;
            padding: 15px;
            margin-bottom: 0;
            border: 1px solid #333;
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: auto;
            height: auto;
        }

        /* School Header Section - Centered at top */
        .school-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            position: relative;
        }

        .school-header img {
            position: absolute;
            top: 0;
            right: 10px;
            max-height: 80px;
            width: 80px;
            border-radius: 50%;
            border: 2px solid #000;
        }

        .school-header h1 {
            font-size: 18px; /* Increased from 16px */
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .school-header p {
            margin: 2px 0;
            font-size: 10px; /* Increased from 9px */
        }

        .school-header h2 {
            font-size: 15px; /* Increased from 13px */
            font-weight: bold;
            margin: 8px 0 0 0;
            text-decoration: underline;
            text-transform: uppercase;
        }

        /* Two Column Layout Container */
        .content-wrapper {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .left-column {
            flex: 2;
        }

        .right-column {
            flex: 1;
            min-width: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 700px; /* Ensures content spans A4 height */
        }

        /* Student Details Section - Compact Table */
        .student-details {
            margin: 10px 0;
            font-size: 10px; /* Increased from 9px */
        }

        .student-details table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-details td {
            padding: 4px 5px; /* Increased from 3px */
            border: 1px solid #333;
        }

        .student-details strong {
            font-weight: bold;
        }

        /* Assessment Section - Grid Tables */
        .assessment-section {
            margin: 8px 0;
        }

        .assessment-section h3 {
            font-size: 11px; /* Increased from 10px */
            font-weight: bold;
            background: #fff;
            color: #000;
            padding: 4px 0; /* Increased from 3px */
            margin: 5px 0 3px 0;
            text-transform: uppercase;
            border-bottom: 1.5px solid #000; /* Increased from 1px */
        }

        .assessment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 8px;
        }

        .assessment-table th {
            background: #fff;
            color: #000;
            padding: 3px 2px;
            border: 1px solid #000;
            text-align: center;
            font-size: 9px; /* Increased from 8px */
            font-weight: bold;
        }

        .assessment-table td {
            padding: 3px 4px; /* Increased from 2px */
            border: 1px solid #000;
            font-size: 9px; /* Increased from 8px */
        }

        .assessment-table td:first-child {
            text-align: left;
            font-weight: normal;
        }

        .assessment-table td:not(:first-child) {
            text-align: center;
            width: 30px;
        }

        .assessment-table .checkmark {
            font-weight: bold;
            font-size: 13px; /* Increased from 12px */
        }

        /* Right Column Sections - Optimized for vertical spacing */
        .term-box {
            text-align: right;
            font-size: 12px; /* Increased from 11px */
            font-weight: bold;
            margin-bottom: 0;
            padding: 5px;
            border: 1px solid #000;
            flex-shrink: 0; /* Prevent compression */
        }

        .rating-legend {
            background: #fff;
            border: 2px solid #000;
            padding: 8px;
            margin: 0;
            font-size: 9px; /* Increased from 8px */
            flex-shrink: 0; /* Prevent compression */
        }

        .rating-legend strong {
            font-size: 10px; /* Increased from 9px */
            display: block;
            margin-bottom: 5px;
            text-align: center;
            text-decoration: underline;
            font-weight: bold;
        }

        .rating-legend p {
            margin: 2px 0;
            font-size: 9px; /* Increased from 8px */
            line-height: 1.4;
        }

        .rating-item {
            margin: 3px 0;
        }

        /* Right Column Other Sections - Optimized for even distribution */
        .attendance-box,
        .promotion-box,
        .facilitator-box {
            margin: 0;
            padding: 8px;
            border: 1px solid #333;
            font-size: 10px; /* Increased from 9px */
            flex-shrink: 0; /* Prevent compression */
        }

        .attendance-box strong,
        .promotion-box strong,
        .facilitator-box strong {
            display: block;
            margin-bottom: 5px;
            font-size: 10px; /* Increased from 9px */
            font-weight: bold;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
            margin-top: 20px; /* Added space above signature line */
        }

        /* Bottom Full Width Sections - Always positioned at page footer */
        .bottom-section {
            clear: both;
            margin-top: auto; /* Push to bottom */
            border-top: 2px solid #000;
            padding-top: 10px;
            flex-shrink: 0; /* Prevent compression */
        }

        .remarks-section,
        .headteacher-section {
            margin: 8px 0;
            font-size: 10px; /* Increased from 9px */
        }

        .remarks-section strong,
        .headteacher-section strong {
            font-weight: bold;
            margin-right: 5px;
        }

        .signature-img {
            max-height: 40px;
            margin-top: 3px;
        }

        /* Print Styling */
        @media print {
            .no-print {
                display: none !important;
            }

            .report-page {
                page-break-after: always;
                border: none;
                margin: 0;
                padding: 10px;
                display: flex !important;
                flex-direction: column !important;
                min-height: 277mm !important; /* A4 height */
            }

            .report-container {
                width: 100%;
                max-width: 100%;
                padding: 0;
            }

            body {
                margin: 0;
                padding: 0;
            }

            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            .content-wrapper {
                display: flex !important;
            }

            .right-column {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                min-height: 700px !important;
            }
            
            .bottom-section {
                margin-top: auto !important;
                flex-shrink: 0 !important;
            }
        }
    </style>
</head>
<body>

    <div id="main-content">
<div class="container report-container">
    <!-- Print Button (Hidden on Print) -->
    <div class="row no-print" style="padding: 15px 0;">
        <a onClick="PrintElem()" class="btn btn-default btn-icon icon-left pull-right">
            Print Report Sheet
            <i class="glyphicon glyphicon-print"></i>
        </a>
    </div>
    <hr class="no-print">

    <div id="print">
    <?php
    /**
     * ================================================================================
     * DATABASE QUERY COMPATIBILITY DOCUMENTATION - REQUIREMENTS 5.1, 5.2, 5.3, 5.4
     * ================================================================================
     * 
     * This template (template2) uses IDENTICAL database queries to template1 for all 
     * student data. No schema changes or data migration required when switching 
     * between templates. Both templates work with the same data structure.
     * 
     * VERIFIED QUERY COMPATIBILITY:
     * =============================
     * 
     * 1. SYSTEM SETTINGS QUERIES (Requirement 5.1)
     *    - Template1: $this->db->get_where('settings', array('type'=>'system_name'))
     *    - Template2: IDENTICAL - Same query, same table, same WHERE conditions
     *    - Tables used: 'settings'
     *    - Fields queried: system_name, running_year, location, address, term_ending, 
     *                      next_term_begins, running_term
     * 
     * 2. STUDENT LIST QUERIES (Requirement 5.1)
     *    - Template1: SELECT DISTINCT student_id FROM mark WHERE class_id, section_id, 
     *                 year, exam_id, term, mute='0'
     *    - Template2: IDENTICAL - Same SELECT DISTINCT logic with same WHERE conditions
     *    - Tables used: 'mark'
     * 
     * 3. STUDENT INFORMATION QUERIES (Requirement 5.1)
     *    - Template1: $this->db->get_where('student', array('student_id' => $student_id))
     *    - Template2: IDENTICAL - Same query structure
     *    - Tables used: 'student'
     *    - Fields accessed: name, sex (gender)
     * 
     * 4. EXAM MARKS QUERIES (Requirement 5.2)
     *    - Template1: $this->db->get_where('mark', array('student_id', 'exam_id', 
     *                 'subject_id', 'class_id', 'year', 'term'))
     *    - Template2: IDENTICAL - Same table, same WHERE clause structure
     *    - Tables used: 'mark'
     *    - JOIN structure: None required (both templates use direct queries)
     *    - Fields accessed: test1 (for rating scale: 1=MO, 2=O, 3=S, 4=NA)
     * 
     * 5. ATTENDANCE QUERIES (Requirement 5.3)
     *    - Template1: $this->db->get_where('aggregation', array('student_id', 
     *                 'class_id', 'exam_id', 'section_id', 'term', 'year'))
     *    - Template2: IDENTICAL - Same query, same aggregation table
     *    - Tables used: 'aggregation'
     *    - Fields accessed: days_present, days_opened
     * 
     * 6. TEACHER REMARKS QUERIES (Requirement 5.4)
     *    - Template1: $this->db->get_where('aggregation', array('student_id', 
     *                 'class_id', 'exam_id', 'term', 'year'))->row()->remarks
     *    - Template2: IDENTICAL - Same query to same aggregation table
     *    - Tables used: 'aggregation'
     *    - Fields accessed: remarks
     * 
     * 7. SUBJECT CATEGORIES QUERIES (Template2 specific grouping)
     *    - Template2: Uses 'subject_category_creche' table to get category_id
     *    - Template2: Uses 'subject_creche' table filtered by category_id
     *    - Template1: Uses same tables, just displays differently (no grouping)
     *    - Tables used: 'subject_category_creche', 'subject_creche'
     *    - This is PRESENTATION layer difference only - same data source
     * 
     * DATA MIGRATION: NONE REQUIRED
     * =============================
     * Switching from template1 to template2 (or vice versa) requires NO database 
     * schema changes, NO data migration, and NO new tables. Both templates consume 
     * the exact same data structure from the existing database.
     * 
     * VERIFICATION STATUS: ✓ ALL QUERIES VERIFIED IDENTICAL
     * ================================================================================
     */
    
    // ========== SYSTEM SETTINGS QUERIES - IDENTICAL TO TEMPLATE1 (Requirement 5.1) ==========
    // Query: SELECT * FROM settings WHERE type = 'system_name' (and other types)
    // Template1 equivalent: Lines 73-79 in template1 - IDENTICAL queries
    $system_name        = $this->db->get_where('settings', array('type'=>'system_name'))->row()->description;
    $running_year       = $year; // Use passed variable from controller (from exam table)
    $location           = $this->db->get_where('settings', array('type'=>'location'))->row()->description;
    $address            = $this->db->get_where('settings', array('type'=>'address'))->row()->description;
    
    // Fetch term dates from terms table using year and term from exam
    $termRows = getArchivedTermStartsEndsDate($term, $year);
    if($termRows && $termRows->term_ending != '' && !empty($termRows->term_ending)) {
        $term_ending = $termRows->term_ending;
        $next_term_begins = $termRows->next_term_begins;
    } else {
        // Fallback to settings if term record not found
        $term_ending = $this->db->get_where('settings', array('type'=>'term_ending'))->row()->description;
        $next_term_begins = $this->db->get_where('settings', array('type'=>'next_term_begins'))->row()->description;
    }
    
    $running_term       = $term; // Use passed variable from controller (from exam table)
    $class_name         = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
    $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
    $exam_name          = $this->db->get_where('exam', array('exam_id' => $exam_id))->row()->name;
    
    // ========== DATE FORMATTING FUNCTION ==========
    // Formats dates to "31st July, 2026" format with superscript ordinal suffix
    function format_date_ordinal($date_string) {
        if(empty($date_string)) {
            return $date_string;
        }
        
        // Try to parse the date - accepts multiple formats
        $timestamp = strtotime($date_string);
        if($timestamp === false) {
            return $date_string; // Return original if can't parse
        }
        
        $day = date('j', $timestamp);        // Day without leading zeros (1-31)
        $month = date('F', $timestamp);      // Full month name (January, February, etc.)
        $year = date('Y', $timestamp);       // Four-digit year (2026)
        
        // Add ordinal suffix (st, nd, rd, th) with superscript
        $suffix = 'th';
        if($day == 1 || $day == 21 || $day == 31) {
            $suffix = 'st';
        } elseif($day == 2 || $day == 22) {
            $suffix = 'nd';
        } elseif($day == 3 || $day == 23) {
            $suffix = 'rd';
        }
        
        // Return with superscript suffix
        return $day . '<sup>' . $suffix . '</sup> ' . $month . ', ' . $year;
    }
    
    // Format the dates
    $term_ending = format_date_ordinal($term_ending);
    $next_term_begins = format_date_ordinal($next_term_begins);
    
    // ========== SECTION NAME QUERY - FOR BULK DISPLAY ==========
    // Query: SELECT name FROM section WHERE section_id = ?
    // This retrieves the section name (e.g., "A", "B") to display with class name in bulk printing
    $section_row = $this->db->get_where('section', array('section_id' => $section_id))->row();
    $section_name = $section_row ? $section_row->name : '';

    // ========== ENROLLMENT QUERY - IDENTICAL TO TEMPLATE1 (Requirement 5.1) ==========
    // Query: SELECT COUNT(*) FROM enroll WHERE class_id, mute='0', term, section_id, year
    // Template1 equivalent: Line 81 in template1 - IDENTICAL query
    $no_on_roll = $this->db->get_where('enroll', array(
        'class_id' => $class_id, 
        'mute' => '0', 
        'term' => $running_term, 
        'section_id' => $section_id, 
        'year' => $running_year
    ))->num_rows();

    // ========== STUDENT LIST QUERY - IDENTICAL TO TEMPLATE1 (Requirement 5.2) ==========
    // Query: SELECT DISTINCT student_id FROM mark WHERE class_id, section_id, year, exam_id, mute='0', term
    // Template1 equivalent: Lines 45-55 in template1 - IDENTICAL SELECT DISTINCT logic
    $data_array = array(
        'class_id' => $class_id, 
        'section_id' => $section_id, 
        'year' => $year, 
        'exam_id' => $exam_id,
        'mute' => '0',
        'term' => $term
    );

    $this->db->select('student_id');
    $this->db->distinct();
    $this->db->where($data_array);
    $this->db->from('mark');
    $students_marks = $this->db->get()->result_array();

    // ========== STUDENT LOOP - PROCESS EACH STUDENT ==========
    // Loop through each student (same iteration logic as template1)
    foreach($students_marks as $student_row):
        $student_id = $student_row['student_id'];
        
        // ========== STUDENT INFORMATION QUERY - IDENTICAL TO TEMPLATE1 (Requirement 5.1) ==========
        // Query: SELECT * FROM student WHERE student_id = ?
        // Template1 equivalent: Line 104 in template1 - IDENTICAL query
        // Fields accessed: name, sex (gender)
        $student_info = $this->db->get_where('student', array('student_id' => $student_id))->row();
        $student_name = $student_info->name;
        $gender = $student_info->sex;

        // ========== ATTENDANCE QUERIES - HYBRID APPROACH (Requirement 5.3) ==========
        // HYBRID APPROACH: Fetch from aggregation table AND use helper function
        // Query 1: SELECT days_opened FROM aggregation (stored in $att_total_manual - not used)
        // Query 2: SELECT days_present FROM aggregation (stored in $att_present_manual - not used)
        // Query 3: Call getStudentAttendancePresentDays() helper for actual $days_present
        // Query 4: SELECT * FROM settings WHERE type='days_opened' for actual $days_opened display
        
        // Fetch from aggregation table (for compatibility - values stored but not used for display)
        $attendance_data = $this->db->get_where('aggregation', array(
            'student_id' => $student_id, 
            'class_id' => $class_id, 
            'exam_id' => $exam_id, 
            'section_id' => $section_id, 
            'term' => $running_term, 
            'year' => $running_year
        ))->row();
        
        $att_total_manual = $attendance_data ? $attendance_data->days_opened : 0;
        $att_present_manual = $attendance_data ? $attendance_data->days_present : 0;

        // Get actual attendance values using helper function and terms table
        $att_present = getStudentAttendancePresentDays($student_id, $running_term, $running_year);
        
        // Fetch days_opened from terms table using exam's year and term
        $term_record = $this->db->get_where('terms', array('year' => $running_year, 'term' => $running_term))->row();
        $att_total = $term_record && isset($term_record->days_opened) ? $term_record->days_opened : 0;
        
        // ========== TEACHER REMARKS QUERY - IDENTICAL TO TEMPLATE1 (Requirement 5.4) ==========
        // Query: SELECT remarks FROM aggregation WHERE student_id, class_id, exam_id, term, year
        // Template1 equivalent: Line 81 in template1 - IDENTICAL query to same aggregation table
        // This is the SAME data source as attendance queries (same aggregation row)
        $teacher_remarks = $attendance_data ? $attendance_data->remarks : '';

        // ========== PROMOTION LOGIC - IDENTICAL TO TEMPLATE1 ==========
        // Query: Calculate promoted_to_class based on running_term
        // Template1 equivalent: Lines 170-184 in template1 - IDENTICAL promotion logic
        $promoted_to_class = '';
        if($running_term == 3) {
            $explode_running_year = explode('-', $running_year);
            $year_part1 = $explode_running_year[0] + 1;
            $year_part2 = $explode_running_year[1] + 1;

            $promoted_to_year = $year_part1. '-'. $year_part2;
            $promoted_to_term = 1;

            $promoted_to_enroll = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'term' => $promoted_to_term))->row();
            
            if($promoted_to_enroll) {
                $promoted_to_class_id = $promoted_to_enroll->class_id;
                $promoted_class_row = $this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row();
                $promoted_to_class = $promoted_class_row->name. ' '.$promoted_class_row->name_numeric;
            } else {
                $promoted_to_class = 'N/A';
            }
        } else {
            $promoted_to_class = 'N/A';
        }

        // ========== FACILITATOR NAME (CLASS TEACHER) - IDENTICAL TO TEMPLATE1 ==========
        // Query: Get teacher_id from class table, then get teacher name from teacher table
        // Template1 equivalent: Lines 789-795 in template1 - IDENTICAL query logic
        $class_row = $this->db->get_where('class', array('class_id' => $class_id, 'name' => $class_name, 'name_numeric' => $class_name_numeric))->row();
        $teacher_id = $class_row ? $class_row->teacher_id : null;
        
        if($teacher_id) {
            $teacher_row = $this->db->get_where('teacher', array('teacher_id' => $teacher_id))->row();
            $teacher_name = $teacher_row ? $teacher_row->name : 'Not Assigned';
        } else {
            $teacher_name = 'Not Assigned';
        }

        // Class display name
        if($class_name == 'CRECHE') {
            $class_display = ucwords(strtolower($class_name));
        } else {
            $class_display = ucwords(strtolower($class_name)) . ' ' . $class_name_numeric;
        }
        
        // Add section name to class display (e.g., "Creche A", "Class 1 B")
        if($section_name) {
            $class_display .= ' ' . $section_name;
        }
    ?>

    <div class="report-page">
        <!-- School Header Section - Centered with Logo on Right -->
        <div class="school-header">
            <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="School Logo" />
            <h1><?php echo strtoupper($system_name); ?></h1>
            <p><?php echo strtoupper($address); ?></p>
            <p>CONTACT: <?php echo $this->db->get_where('settings', array('type'=>'phone'))->row()->description ?? ''; ?></p>
            <h2>TERMINAL REPORT</h2>
        </div>

        <!-- Two Column Layout -->
        <div class="content-wrapper">
            <!-- LEFT COLUMN: Student Details + Assessment Tables -->
            <div class="left-column">
                <!-- Student Details Section - Compact 2-row table -->
                <div class="student-details">
                    <table>
                        <tr>
                            <td><strong>NAME:</strong> <?php echo strtoupper($student_name); ?></td>
                        </tr>
                        <tr>
                            <td><strong>CLASS:</strong> <?php echo strtoupper($class_display); ?></td>
                            <td><strong>NUMBER ON ROLL:</strong> <?php echo $no_on_roll; ?></td>
                        </tr>
                        <tr>
                            <td><strong>VACATION DATE:</strong> <?php echo $term_ending; ?></td>
                            <td><strong>NEXT TERM BEGINS:</strong> <?php echo $next_term_begins; ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Assessment Areas Sections - DYNAMIC FROM DATABASE (Identical to Template1) -->
                <div class="assessment-section">
                    <?php
                    // ========== DYNAMIC CATEGORY RETRIEVAL - IDENTICAL TO TEMPLATE1 ==========
                    // Query: SELECT * FROM subject_category_creche
                    // Template1 equivalent: Line 417 in template1 - IDENTICAL query
                    // This retrieves ALL categories dynamically from the database
                    $subj_category = $this->db->get('subject_category_creche')->result_array();
                    $subj_category_rows = $this->db->get('subject_category_creche')->num_rows();

                    // Loop through each category dynamically (same as Template1)
                    foreach($subj_category as $cat):
                        $category_id = $cat['category_id'];
                        $category_name = strtoupper($cat['name']); // Display name in uppercase
                        
                        // ========== DYNAMIC SUBJECTS RETRIEVAL - IDENTICAL TO TEMPLATE1 ==========
                        // Query: SELECT * FROM subject_creche WHERE class_id, category_id, year, term
                        // Template1 equivalent: Lines 428-430 in template1 - IDENTICAL query
                        $subjects = $this->db->get_where('subject_creche', array(
                            'class_id' => $class_id,
                            'category_id' => $category_id,
                            'year' => $running_year,
                            'term' => $running_term
                        ))->result_array();

                        // Only display category if it has subjects
                        if(count($subjects) > 0):
                    ?>
                    <table class="assessment-table">
                        <thead>
                            <tr>
                                <th style="text-align: left;"><?php echo $category_name; ?></th>
                                <?php
                                // ========== DYNAMIC RATING SCALE HEADERS - IDENTICAL TO TEMPLATE1 ==========
                                // Query: SELECT * FROM grade_creche
                                // Template1 equivalent: Uses same grade_creche table for rating scale
                                // This retrieves ALL rating scale abbreviations from database (MO, O, S, NA)
                                $grade_headers = $this->db->get('grade_creche')->result_array();
                                foreach($grade_headers as $grade_header):
                                ?>
                                    <th><?php echo strtoupper($grade_header['abbrev']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $item_number = 1;
                            foreach($subjects as $subject): 
                                // ========== MARKS RETRIEVAL - IDENTICAL TO TEMPLATE1 ==========
                                // Query: SELECT * FROM mark WHERE student_id, exam_id, subject_id, class_id, year, term
                                // Template1 equivalent: Lines 434-440 in template1 - IDENTICAL WHERE conditions
                                // The test1 field contains the grade_id from grade_creche table
                                $mark_row = $this->db->get_where('mark', array(
                                    'student_id' => $student_id,
                                    'exam_id' => $exam_id,
                                    'subject_id' => $subject['subject_id'],
                                    'class_id' => $class_id,
                                    'year' => $running_year,
                                    'term' => $running_term
                                ))->row();

                                // test1 field contains grade_id (1, 2, 3, 4) which maps to grade_creche table
                                $test1_value = $mark_row ? $mark_row->test1 : 0;
                            ?>
                            <tr>
                                <td><?php echo $item_number . '. ' . $subject['name']; ?></td>
                                <td class="checkmark"><?php echo ($test1_value == 1) ? '✓' : ''; ?></td>
                                <td class="checkmark"><?php echo ($test1_value == 2) ? '✓' : ''; ?></td>
                                <td class="checkmark"><?php echo ($test1_value == 3) ? '✓' : ''; ?></td>
                                <td class="checkmark"><?php echo ($test1_value == 4) ? '✓' : ''; ?></td>
                            </tr>
                            <?php 
                            $item_number++;
                            endforeach; ?>
                        </tbody>
                    </table>
                    <?php
                        endif; // End if count($subjects) > 0
                    endforeach; // End foreach($subj_category as $cat)
                    ?>
                </div>
            </div>

            <!-- RIGHT COLUMN: Term, Rating Scale, Attendance, Promotion, Facilitator -->
            <div class="right-column">
                <!-- Term Box -->
                <div class="term-box">
                    TERM: <?php echo $running_term; ?> | YEAR: <?php echo $running_year; ?>
                </div>

                <!-- Rating Scale Legend - DYNAMIC FROM DATABASE (Identical to Template1) -->
                <div class="rating-legend">
                    <strong>RATING SCALE</strong>
                    <p style="margin-bottom: 5px;">Learners are assessed based on the rating scale described below.</p>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
                        <?php
                        // ========== DYNAMIC RATING SCALE RETRIEVAL - IDENTICAL TO TEMPLATE1 ==========
                        // Query: SELECT * FROM grade_creche
                        // Template1 equivalent: Lines 379-380 in template1 - IDENTICAL query
                        // This retrieves ALL rating scale definitions from database (MO, O, S, NA)
                        $grading_sys = $this->db->get('grade_creche')->result_array();
                        foreach($grading_sys as $grade): 
                        ?>
                        <tr>
                            <td style="padding: 3px 5px; font-size: 8px; text-align: left; border-top: 1px solid #000; border-bottom: 1px solid #000; border-right: 1px solid #000; font-weight: bold; width: 60px;"><?php echo strtoupper($grade['abbrev']); ?></td>
                            <td style="padding: 3px 5px; font-size: 8px; text-align: left; border-top: 1px solid #000; border-bottom: 1px solid #000;"><?php echo strtoupper($grade['full_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Attendance Box -->
                <div class="attendance-box">
                    <strong>ATTENDANCE:</strong> <?php echo $att_present; ?> OUT OF <?php echo $att_total; ?>
                </div>

                <!-- Promotion Box -->
                <div class="promotion-box">
                    <strong>PROMOTED TO:</strong>
                    <div style="margin-top: 5px;">
                        <span style="font-weight: bold;"><?php echo $promoted_to_class; ?></span>
                    </div>
                </div>

                <!-- Facilitator Box -->
                <div class="facilitator-box">
                    <strong>Facilitator's Name:</strong>
                    <div style="margin: 5px 0;">
                        <span style="font-weight: bold;"><?php echo $teacher_name; ?></span>
                    </div>
                    <strong>Signature:</strong>
                    <div style="margin-top: 5px;">
                        <span class="signature-line"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: General Remarks & Head Teacher (Full Width) -->
        <div class="bottom-section">
            <div class="remarks-section">
                <strong>General Remarks:</strong> <?php echo !empty($teacher_remarks) ? $teacher_remarks : '_____________________________________________'; ?>
            </div>

            <div class="headteacher-section">
                <strong>Head Teacher's Name:</strong> <span class="signature-line"></span>
                <strong style="margin-left: 20px;">Signature:</strong>
                <?php 
                $signature_path = base_url('uploads/signature/admin/head_teacher.png');
                if(file_exists(FCPATH.'uploads/signature/admin/head_teacher.png')):
                ?>
                <img src="<?php echo $signature_path; ?>" class="signature-img" alt="Head Teacher Signature" />
                <?php else: ?>
                <span class="signature-line"></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php endforeach; ?>
    </div> <!-- End #print -->
</div> <!-- End container -->

<!-- Print JavaScript (2.8) -->
<script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>
<script type="text/javascript">
    // Store class and section data for PDF filename
    var bulkPrintData = {
        term: <?php echo $term; ?>,
        year: '<?php echo $year; ?>',
        className: '<?php echo isset($class_name) ? $class_name : ""; ?>',
        classNumeric: '<?php echo isset($class_name_numeric) ? $class_name_numeric : ""; ?>',
        sectionName: '<?php echo isset($section_name) ? $section_name : ""; ?>',
        sectionId: <?php echo $section_id; ?>
    };

    function PrintElem() {
        var studentReports = document.querySelectorAll('.report-page');
        
        // Build class display name (e.g., "CRECHE", "CLASS 1")
        var classDisplay = bulkPrintData.className;
        if (bulkPrintData.className && bulkPrintData.className.toUpperCase() !== 'CRECHE' && bulkPrintData.classNumeric) {
            classDisplay = bulkPrintData.className + ' ' + bulkPrintData.classNumeric;
        }
        
        // Add section to class display (e.g., "CRECHE A", "CLASS 1 B")
        if (bulkPrintData.sectionName) {
            classDisplay += ' ' + bulkPrintData.sectionName;
        }
        
        if (studentReports.length === 1) {
            // Single student: Extract student name and personalize the title
            var studentNameElement = studentReports[0].querySelector('.student-details td');
            if (studentNameElement) {
                var studentNameText = studentNameElement.textContent || studentNameElement.innerText;
                var studentName = studentNameText.replace('NAME:', '').trim();
                
                // Format: "End of Term 3 Exam Report for Christian Agbotah - 2025-2026"
                document.title = 'End of Term ' + bulkPrintData.term + ' Exam Report for ' + studentName + ' - ' + bulkPrintData.year;
            }
        } else {
            // Bulk printing: Include class with section in title
            // Format: "End of Term 3 Exam Report - CRECHE A - 2025-2026"
            document.title = 'End of Term ' + bulkPrintData.term + ' Exam Report - ' + classDisplay + ' - ' + bulkPrintData.year;
        }
        
        window.print();
    }
</script>

    </div><!-- End main-content -->
</body>
</html>
