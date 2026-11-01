<?php
    // Load subject code helper
    $this->load->helper('subject_code');
    
    $class_row          =   $this->db->get_where('class' , array('class_id' => $class_id))->row();
    $class_name         =   $class_row->name;
    $class_name_numeric =   $class_row->name_numeric;
    $class_category     =   $class_row->category;
    $exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
    $system_name_row    =   $this->db->get_where('settings' , array('type'=>'system_name'))->row();
    $system_name        =   $system_name_row ? $system_name_row->description : 'School';
    
    // Use passed variables from controller (fetched from exam table), not settings
    // $running_year, $running_term, $running_sem are already set by controller from exam record
    
    $location_row       =   $this->db->get_where('settings' , array('type'=>'location'))->row();
    $location           =   $location_row ? $location_row->description : '';
    $address_row        =   $this->db->get_where('settings' , array('type'=>'address'))->row();
    $address            =   $address_row ? $address_row->description : '';
    $phone_row          =   $this->db->get_where('settings' , array('type'=>'phone'))->row();
    $phone              =   $phone_row ? $phone_row->description : '';
    
    // Fetch term/semester dates from terms table using exam's year and term/sem
    $is_jhs = ($class_name == 'FORM');
    if($is_jhs && !empty($running_sem)) {
        // For semester-based (FORM/JHSS)
        $termRows = getArchivedTermStartsEndsDate($running_sem, $running_year);
        if($termRows && $termRows->term_ending != '' && !empty($termRows->term_ending)) {
            $sem_ending = $termRows->term_ending;
            $next_sem_begins = $termRows->next_term_begins;
        } else {
            $sem_ending_row = $this->db->get_where('settings', array('type'=>'sem_ending'))->row();
            $sem_ending = $sem_ending_row ? $sem_ending_row->description : date('d-m-Y');
            $next_sem_begins_row = $this->db->get_where('settings', array('type'=>'next_sem_begins'))->row();
            $next_sem_begins = $next_sem_begins_row ? $next_sem_begins_row->description : date('d-m-Y', strtotime('+1 month'));
        }
        $term_ending = $sem_ending;
        $next_term_begins = $next_sem_begins;
    } else {
        // For term-based (regular classes)
        $termRows = getArchivedTermStartsEndsDate($running_term, $running_year);
        if($termRows && $termRows->term_ending != '' && !empty($termRows->term_ending)) {
            $term_ending = $termRows->term_ending;
            $next_term_begins = $termRows->next_term_begins;
        } else {
            $term_ending_row = $this->db->get_where('settings', array('type'=>'term_ending'))->row();
            $term_ending = $term_ending_row ? $term_ending_row->description : date('d-m-Y');
            $next_term_begins_row = $this->db->get_where('settings', array('type'=>'next_term_begins'))->row();
            $next_term_begins = $next_term_begins_row ? $next_term_begins_row->description : date('d-m-Y', strtotime('+1 month'));
        }
    }

    // Determine if JHS (semester-based) or non-JHS (term-based)
    $is_jhs = ($class_name == 'FORM');
    
    if($is_jhs) {
        $period_type = 'sem';
        $period_value = $running_sem;
        $period_label = 'SEMESTER';
        $period_ending = $sem_ending;
        $next_period_begins = $next_sem_begins;
    } else {
        $period_type = 'term';
        $period_value = $running_term;
        $period_label = 'TERM';
        $period_ending = $term_ending;
        $next_period_begins = $next_term_begins;
    }

    $no_on_roll = $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0', $period_type => $period_value, 'section_id' => $section_id, 'year' => $running_year))->num_rows();
    
    $aggregation_data = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, $period_type => $period_value, 'year' => $running_year))->row();
    
    $form_master_remarks = $aggregation_data ? $aggregation_data->remarks : '';
    $interest = $aggregation_data && $aggregation_data->interest ? strtoupper($aggregation_data->interest) : '';
    
    // Fetch days_opened from terms table
    $period_term = $period_type == 'term' ? $period_value : ($period_type == 'sem' ? $period_value : 1);
    $term_record = $this->db->get_where('terms', array('year' => $running_year, 'term' => $period_term))->row();
    $att_total = $term_record && isset($term_record->days_opened) ? $term_record->days_opened : 0;
    
    // Calculate days_present from attendance table
    $this->db->select('COUNT(*) as days_present');
    $this->db->from('attendance');
    $this->db->where('student_id', $student_id);
    $this->db->where('year', $running_year);
    $this->db->where($period_type, $period_value);
    $this->db->where_in('status', array(1, 3)); // Status: 1=Present, 3=Late
    $att_present_query = $this->db->get();
    $att_present = $att_present_query->row() ? (int)$att_present_query->row()->days_present : 0;

    $grading = $this->db->get('grade')->result_array();
    $gender = $this->db->get_where('student', array('student_id' => $student_id))->row()->sex;
    $student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
    $student_code = $this->db->get_where('student', array('student_id' => $student_id))->row()->student_code;
    
    // Get form master name
    $form_master_id = $this->db->get_where('class', array('class_id' => $class_id))->row()->teacher_id;
    $form_master_name = '';
    if($form_master_id) {
        $form_master_row = $this->db->get_where('teacher', array('teacher_id' => $form_master_id))->row();
        if($form_master_row) {
            $form_master_name = strtoupper($form_master_row->name);
        }
    }
    
    // Get headmaster name
    $headmaster_row = $this->db->get_where('settings', array('type' => 'headmaster_name'))->row();
    $headmaster_name = $headmaster_row ? strtoupper($headmaster_row->description) : '';
    
    // Get head teacher remarks from aggregation table
    $headmaster_remarks = '';
    if($aggregation_data && isset($aggregation_data->head_teacher_remarks)) {
        $headmaster_remarks = $aggregation_data->head_teacher_remarks;
    }
    
    // If head teacher remark is empty, get auto remark based on percentage
    if (empty($headmaster_remarks) || trim($headmaster_remarks) == '') {
        $this->load->model('Head_teacher_remarks_model');
        
        // Get percentage from aggregation data
        $student_percentage = $aggregation_data && isset($aggregation_data->percentage) 
            ? round($aggregation_data->percentage) 
            : 0;
        
        // If percentage available, get auto remark
        if ($student_percentage > 0) {
            $auto_head_remark = $this->Head_teacher_remarks_model->find_by_percentage($student_percentage);
            
            if ($auto_head_remark && !empty($auto_head_remark->remark_text)) {
                $headmaster_remarks = $auto_head_remark->remark_text;
            }
        }
    }
    
    // Check promotion status and promoted class (same logic as style 1)
    $promotion_status = '';
    $promoted_to_class = '';
    
    // Store current class_id for comparison
    $current_class_id = $class_id;
    
    // Check if JHS (semester-based) or non-JHS (term-based)
    if($is_jhs) {
        // Semester-based promotion logic
        if($running_sem == 2) {
            // End of second semester - check if promoted to next year
            $explode_running_year = explode('-', $running_year);
            $year_part1 = $explode_running_year[0] + 1;
            $year_part2 = $explode_running_year[1] + 1;

            $promoted_to_year = $year_part1. '-'. $year_part2;
            $promoted_to_sem = 1;

            // Check if enrolled in next year
            $promoted_enrollment = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'sem' => $promoted_to_sem));
            if($promoted_enrollment->num_rows() > 0) {
                $promoted_to_class_id = $promoted_enrollment->row()->class_id;
                
                // Check if promoted or repeated by comparing class IDs
                if($promoted_to_class_id == $current_class_id) {
                    $promotion_status = 'Repeated';
                } else {
                    $promotion_status = 'Promoted';
                }
            }
        } else {
            // First semester - check if promoted to second semester of same year
            $promoted_to_year = $running_year;
            $promoted_to_sem = 2;
            
            $promoted_enrollment = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'sem' => $promoted_to_sem));
            if($promoted_enrollment->num_rows() > 0) {
                $promoted_to_class_id = $promoted_enrollment->row()->class_id;
                
                // Check if promoted or repeated by comparing class IDs
                if($promoted_to_class_id == $current_class_id) {
                    $promotion_status = 'Repeated';
                } else {
                    $promotion_status = 'Promoted';
                }
            }
        }
    } else {
        // Term-based promotion logic (for non-JHS classes)
        if($running_term == 3) {
            // End of third term - check if promoted to next year
            $explode_running_year = explode('-', $running_year);
            $year_part1 = $explode_running_year[0] + 1;
            $year_part2 = $explode_running_year[1] + 1;

            $promoted_to_year = $year_part1. '-'. $year_part2;
            $promoted_to_term = 1;

            // Check if enrolled in next year
            $promoted_enrollment = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'term' => $promoted_to_term));
            if($promoted_enrollment->num_rows() > 0) {
                $promoted_to_class_id = $promoted_enrollment->row()->class_id;
                
                // Check if promoted or repeated by comparing class IDs
                if($promoted_to_class_id == $current_class_id) {
                    $promotion_status = 'Repeated';
                } else {
                    $promotion_status = 'Promoted';
                }
            }
        }
        // For terms 1 and 2, no promotion status is shown (student continues in same class)
    }
    
    // If no promotion found in enroll table, check promotion table as fallback
    if(empty($promotion_status) && $this->db->table_exists('promotion')) {
        $promotion_query = $this->db->get_where('promotion', array(
            'student_id' => $student_id,
            'from_year' => $running_year
        ));
        if($promotion_query->num_rows() > 0) {
            $promo_data = $promotion_query->row();
            // Try to get the promoted class ID
            if(isset($promo_data->to_class_id)) {
                // Check if promoted or repeated by comparing class IDs
                if($promo_data->to_class_id == $current_class_id) {
                    $promotion_status = 'Repeated';
                } else {
                    $promotion_status = 'Promoted';
                }
            } else {
                $promotion_status = 'Promoted';
            }
        }
    }
    
    // Vacation date
    $vacation_date_str = date('d F, Y', strtotime($period_ending));
    $next_period_str = date('d F, Y', strtotime($next_period_begins));
    
    // Function to get ordinal suffix (1st, 2nd, 3rd, etc.)
    function get_ordinal_suffix($number) {
        if ($number % 100 >= 11 && $number % 100 <= 13) {
            return $number . 'TH';
        }
        switch ($number % 10) {
            case 1: return $number . 'ST';
            case 2: return $number . 'ND';
            case 3: return $number . 'RD';
            default: return $number . 'TH';
        }
    }
?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Sheet - <?php echo strtoupper($student_name); ?></title>
    
    <?php include(APPPATH . 'views/backend/includes/loading_screen.php'); ?>
    
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            background: #f5f5f5;
        }
        
        .page-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 5mm 8mm;
            background: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
            page-break-after: always;
        }
        
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 0 1 auto;
            display: flex;
            flex-direction: column;
        }
        
        #print_div {
            padding: 10px;
            text-align: center;
            background: #333;
            margin-bottom: 20px;
        }
        
        #print_div a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 25px;
            background: #28a745;
            border-radius: 5px;
            display: inline-block;
        }
        
        #print_div a:hover {
            background: #218838;
        }
        
        .header-section {
            position: relative;
            text-align: center;
            margin-bottom: 4px;
            padding: 0 100px;
        }
        
        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 100px;
            height: 100px;
        }
        
        .student-photo {
            position: absolute;
            right: 0;
            top: 0;
            width: 90px;
            height: 110px;
            border: none;
            object-fit: cover;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        
        .school-address {
            font-size: 10px;
            margin-bottom: 2px;
            line-height: 1.4;
        }
        
        .school-contact {
            font-size: 10px;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 6px;
            letter-spacing: 1.5px;
        }
        
        .student-info {
            margin-bottom: 8px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            position: relative;
        }
        
        .info-row-wrapper {
            display: flex;
            gap: 10px;
        }
        
        .info-row-first {
            display: flex;
            gap: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }
        
        .info-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-item {
            font-size: 10px;
            padding: 2px 0;
        }
        
        .info-item-highlighted {
            font-size: 12px;
            padding: 2px 0;
        }
        
        .info-label {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .info-value {
            text-transform: uppercase;
        }
        
        .info-value-bold {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .grading-header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 12px 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        
        .subjects-table th {
            background: #d3d3d3;
            border: 1px solid #000;
            padding: 8px 4px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            text-transform: uppercase;
            line-height: 1.3;
        }
        
        .subjects-table td {
            border: 1px solid #000;
            padding: 10px 4px;
            text-align: center;
            font-size: 11px;
            word-wrap: break-word;
            line-height: 1.4;
        }
        
        .subjects-table .subject-col {
            text-align: left;
            padding-left: 5px;
            text-transform: uppercase;
        }
        
        .subjects-table .code-col {
            font-weight: bold;
        }
        
        .remarks-section {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .remarks-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .remarks-label {
            display: table-cell;
            width: 180px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            vertical-align: top;
            padding: 4px 0;
        }
        
        .remarks-content {
            display: table-cell;
            font-size: 11px;
            padding: 4px 0;
            line-height: 1.5;
        }
        
        .signature-section {
            margin-top: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature-item {
            flex: 1;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            margin-top: 6px;
        }
        
        .footer-container {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 10px;
            margin-top: auto;
            padding-top: 4px;
            flex-shrink: 0;
        }
        
        .footer-left {
            flex: 1;
            max-width: 450px;
            font-size: 7px;
            line-height: 1.4;
            color: #333;
        }
        
        .footer-motto {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .footer-text {
            margin-bottom: 2px;
        }
        
        .footer-generated {
            margin-top: 4px;
            color: #666;
            font-size: 6px;
        }
        
        .qr-section {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            flex-shrink: 0;
        }
        
        .qr-section img {
            width: 80px;
            height: 80px;
            display: block;
            margin-bottom: 2px;
        }
        
        .qr-title {
            font-weight: bold;
            font-size: 7px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        
        .qr-instructions {
            font-size: 6px;
            line-height: 1.2;
            max-width: 160px;
            text-align: right;
        }
        
            @media print {
            #print_div {
                display: none !important;
            }
            
            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }
            
            .page-container {
                width: 100%;
                height: 297mm;
                margin: 0;
                padding: 5mm 8mm;
                position: relative;
                page-break-after: always;
                page-break-inside: avoid;
            }
            
            .footer-container {
                page-break-inside: avoid;
            }
            
            @page {
                size: A4;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    
    <div id="main-content">
    <div id="print_div">
        <a href="javascript:void(0);" onClick="window.print()">Print Report Sheet</a>
    </div>
    
    <div class="page-container">
        <div class="content-wrapper">
        <div class="main-content">
        <div class="header-section">
            <img src="<?php echo base_url(); ?>uploads/school_logo.png" class="logo" alt="School Logo">
            <img src="<?php echo $this->crud_model->get_image_url('student',$student_id, $gender);?>" class="student-photo" alt="Student Photo">
            
            <div class="school-name"><?php echo strtoupper($system_name);?></div>
            <div class="school-address"><?php echo strtoupper($location);?></div>
            <?php if($address): ?>
            <div class="school-address"><?php echo strtoupper($address);?></div>
            <?php endif; ?>
            <div class="school-contact"><?php echo $phone;?></div>
            <div class="report-title">REPORT SHEET</div>
        </div>
        
        <div class="student-info">
            <!-- Row 1: NAME and ID with connecting line -->
            <div class="info-row-first">
                <div class="info-column">
                    <div class="info-item-highlighted">
                        <span class="info-label">NAME:</span>
                        <span class="info-value-bold"><?php echo strtoupper($student_name); ?></span>
                    </div>
                </div>
                <div class="info-column">
                    <!-- Empty space for alignment -->
                </div>
                <div class="info-column">
                    <div class="info-item-highlighted">
                        <span class="info-label">ID:</span>
                        <span class="info-value-bold"><?php echo $student_code; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Rows 2-5: Other data -->
            <div class="info-row-wrapper">
                <!-- Column 1 -->
                <div class="info-column">
                    <div class="info-item">
                        <span class="info-label">CLASS:</span>
                        <span class="info-value"><?php 
                            $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
                            echo strtoupper($class_name). ' '. $class_name_numeric.' '.$section_name; 
                        ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">SESSION:</span>
                        <span class="info-value"><?php echo $running_year; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><?php echo strtoupper($period_label); ?>:</span>
                        <span class="info-value"><?php 
                            $period_display = $is_jhs ? $running_sem : $running_term;
                            echo get_ordinal_suffix($period_value) . ' (' . $period_display . ')'; 
                        ?></span>
                    </div>
                </div>
                
                <!-- Column 2 -->
                <div class="info-column">
                    <div class="info-item">
                        <span class="info-label">SECTION:</span>
                        <span class="info-value"><?php echo strtoupper($class_category); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">AVERAGE SCORE:</span>
                        <span class="info-value"><?php 
                            $subjects = $this->db->get_where('subject', array(
                                'class_id' => $class_id, 
                                'year' => $running_year, 
                                $period_type => $period_value
                            ))->result_array();
                            
                            $total_marks = 0;
                            $subject_count = 0;
                            foreach ($subjects as $subject) {
                                $mark_query = $this->db->get_where('mark', array(
                                    'subject_id' => $subject['subject_id'],
                                    'exam_id' => $exam_id,
                                    'class_id' => $class_id,
                                    'student_id' => $student_id,
                                    'year' => $running_year,
                                    $period_type => $period_value
                                ));
                                if($mark_query->num_rows() > 0) {
                                    $mark = $mark_query->row()->mark_obtained;
                                    if($mark !== null && $mark !== '') {
                                        $total_marks += $mark;
                                        $subject_count++;
                                    }
                                }
                            }
                            $average = ($subject_count > 0) ? round($total_marks / $subject_count, 1) : 0;
                            echo $average;
                        ?></span>
                    </div>
                </div>
                
                <!-- Column 3 -->
                <div class="info-column">
                    <div class="info-item">
                        <span class="info-label">NO. IN CLASS:</span>
                        <span class="info-value"><?php echo $no_on_roll; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">TOTAL ATTENDANCE:</span>
                        <span class="info-value"><?php echo $att_present . ' / ' . $att_total; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">VACATION DATE:</span>
                        <span class="info-value"><?php echo strtoupper(date('d F, Y', strtotime($period_ending))); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">NEXT <?php echo strtoupper($period_label); ?> BEGINS:</span>
                        <span class="info-value"><?php echo strtoupper(date('d F, Y', strtotime($next_period_begins))); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grading-header">GRADING SYSTEM:</div>
        
        <table class="subjects-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 200px;">SUBJECT</th>
                    <th style="width: 60px;">CODE</th>
                    <th style="width: 70px;">CLASS<br>(50%)</th>
                    <th style="width: 70px;">EXAM<br>(50%)</th>
                    <th style="width: 70px;">TOTAL<br>(100%)</th>
                    <th style="width: 60px;">GRADE</th>
                    <th style="width: 120px;">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $class_score_total = 0;
                    $exam_score_total = 0;
                    $total_marks_sum = 0;
                    
                    $i = 1;
                    foreach ($subjects as $subject):
                        $subject_code = get_subject_code_with_fallback($subject);
                ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td class="subject-col"><?php echo strtoupper($subject['name']); ?></td>
                    <td class="code-col"><?php echo $subject_code; ?></td>
                    <td>
                        <?php
                            $mark_query = $this->db->get_where('mark', array(
                                'subject_id' => $subject['subject_id'],
                                'exam_id' => $exam_id,
                                'class_id' => $class_id,
                                'student_id' => $student_id,
                                'year' => $running_year,
                                $period_type => $period_value
                            ));
                            
                            $class_score = '';
                            $exam_score = '';
                            $mark_obtained = '';
                            $grade_point = '';
                            $grade_name = '';
                            
                            if($mark_query->num_rows() > 0) {
                                $mark_row = $mark_query->row();
                                $class_score = $mark_row->class_score;
                                $exam_score = $mark_row->exam_score;
                                $mark_obtained = $mark_row->mark_obtained;
                                
                                if($class_score !== null && $class_score !== '') {
                                    echo $class_score;
                                    $class_score_total += $class_score;
                                }
                            }
                        ?>
                    </td>
                    <td>
                        <?php
                            if($exam_score !== null && $exam_score !== '') {
                                echo $exam_score;
                                $exam_score_total += $exam_score;
                            }
                        ?>
                    </td>
                    <td>
                        <?php
                            if($mark_obtained !== null && $mark_obtained !== '') {
                                echo $mark_obtained;
                                $total_marks_sum += $mark_obtained;
                            }
                        ?>
                    </td>
                    <td>
                        <?php
                            if($mark_obtained !== null && $mark_obtained !== '') {
                                $grade = $this->crud_model->get_grade($mark_obtained);
                                echo '<strong>' . $grade['grade_point'] . '</strong>';
                            }
                        ?>
                    </td>
                    <td>
                        <?php
                            if($mark_obtained !== null && $mark_obtained !== '') {
                                $grade = $this->crud_model->get_grade($mark_obtained);
                                echo strtoupper($grade['name']);
                            }
                        ?>
                    </td>
                </tr>
                <?php 
                    $i++;
                    endforeach;
                ?>
            </tbody>
        </table>
        
        <div class="remarks-section">
            <div class="remarks-row">
                <div class="remarks-label">FORM MASTER:</div>
                <div class="remarks-content"><?php echo $form_master_name; ?></div>
                <div class="remarks-label" style="width: 120px; text-align: right; padding-right: 10px;">OVERALL SCORE:</div>
                <div class="remarks-content" style="width: 80px;"><?php echo $total_marks_sum; ?></div>
                <div class="remarks-content" style="text-align: right;"><strong>PROMOTION:</strong> <span style="font-weight: normal;"><?php echo $promotion_status; ?></span></div>
            </div>
            
            <div class="remarks-row">
                <div class="remarks-label">FORM MASTER'S REMARKS:</div>
                <div class="remarks-content"><?php echo ucfirst(strtolower($form_master_remarks)); ?></div>
            </div>
            
            <div class="remarks-row">
                <div class="remarks-label">PRINCIPAL'S REMARKS:</div>
                <div class="remarks-content"><?php echo ucfirst(strtolower($headmaster_remarks)); ?></div>
            </div>
        </div>
        
        <div class="signature-section">
            <div style="text-align: right; width: 100%; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                <span style="font-size: 11px; font-weight: bold;">PRINCIPAL'S SIGNATURE:</span>
                <img src="<?php echo $this->crud_model->get_head_teacher_signature();?>" style="vertical-align: middle; max-height: 60px; max-width: 150px;" alt="Principal Signature" />
            </div>
        </div>
        </div><!-- close main-content -->
        </div><!-- close content-wrapper -->
        
        <div class="footer-container">
            <div class="footer-left">
                <div style="font-weight: bold; font-size: 9px; margin-bottom: 4px;">SCAN QR CODE TO VERIFY</div>
                <div style="font-weight: bold; font-size: 7px; margin-bottom: 3px; line-height: 1.4;">
                    Scan the QR code to verify the authenticity of this report sheet
                </div>
                <div style="font-weight: bold; font-size: 7px; line-height: 1.4;">
                    To scan, download any QR code scanner from your app store or use phone Camera
                </div>
            </div>
            
            <div class="qr-section">
                <?php
                    // Load QR code helper
                    $this->load->helper('qrcode');
                    
                    // Generate unique QR data for this student's report
                    $qr_data = base_url() . 'verify/' . $student_code . '/' . $exam_id . '/' . $running_year;
                    
                    // Get school logo path
                    $logo_path = 'uploads/logo.png';
                    
                    // Generate QR code with logo overlay
                    $qr_image = generate_qr_with_logo($qr_data, $logo_path, 300, 25);
                ?>
                <img src="<?php echo $qr_image; ?>" alt="QR Code" style="width: 150px; height: 150px;">
                <div style="font-size: 6px; margin-top: 3px; text-align: right;">
                    Generated on <?php echo date('M d, Y \a\t g:i:s A'); ?> | <?php echo strtoupper($system_name); ?>
                </div>
            </div>
        </div>
    </div>
    
    </div><!-- End main-content -->

    <script type="text/javascript">
        // Auto print disabled for preview
    </script>
</body>
</html>
