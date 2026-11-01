<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_phrase('lesson_note'); ?> - <?php echo $lesson_note->title; ?></title>
    <style>
        /* Print Styles */
        @page {
            size: A4;
            margin: 15mm;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }
        
        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
            }
            .print-container {
                max-width: 210mm;
                margin: 0 auto;
                background: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
        }
        
        .print-container {
            padding: 20mm;
        }
        
        /* Header */
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .school-name {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .school-address {
            font-size: 10pt;
            margin-top: 5px;
        }
        
        .document-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            padding: 5px;
            border: 1px solid #000;
            display: inline-block;
        }
        
        /* Content Sections */
        .section {
            margin-bottom: 15px;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 8px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 25%;
            font-weight: bold;
            padding: 3px 10px 3px 0;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            padding: 3px 0;
            vertical-align: top;
        }
        
        .content-box {
            border: 1px solid #000;
            padding: 10px;
            min-height: 60px;
            margin-bottom: 10px;
        }
        
        .content-box-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 5px;
            text-decoration: underline;
        }
        
        /* Lists */
        ul.simple-list {
            list-style-type: disc;
            margin-left: 20px;
            padding-left: 0;
        }
        
        ul.simple-list li {
            margin-bottom: 3px;
        }
        
        /* Competencies */
        .competency-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .competency-tag {
            border: 1px solid #000;
            padding: 2px 8px;
            font-size: 10pt;
        }
        
        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        /* Signature Section */
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: bottom;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
        
        .signature-title {
            font-weight: bold;
            font-size: 10pt;
        }
        
        .signature-date {
            font-size: 9pt;
            color: #666;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 9pt;
            text-align: center;
            color: #666;
        }
        
        /* Print Button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .print-button:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <!-- Print Button (Screen Only) -->
    <button class="print-button no-print" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 5.5V7h1.5a.5.5 0 0 1 0 1H9v1.5a.5.5 0 0 1-1 0V8H6.5a.5.5 0 0 1 0-1H8V5.5a.5.5 0 0 1 1 0z"/>
        </svg>
        Print Document
    </button>

    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="school-name"><?php echo $school_name; ?></div>
            <?php if ($school_address): ?>
            <div class="school-address"><?php echo $school_address; ?></div>
            <?php endif; ?>
            <?php if ($school_phone || $school_email): ?>
            <div class="school-address">
                <?php echo $school_phone; ?>
                <?php if ($school_phone && $school_email): ?> | <?php endif; ?>
                <?php echo $school_email; ?>
            </div>
            <?php endif; ?>
            <div class="document-title">GES Standard-Based Lesson Note</div>
        </div>

        <!-- Basic Information -->
        <div class="section">
            <table class="data-table">
                <tr>
                    <th style="width: 25%;">Subject</th>
                    <td style="width: 25%;"><?php echo $lesson_note->subject_name; ?></td>
                    <th style="width: 25%;">Class</th>
                    <td style="width: 25%;"><?php echo $lesson_note->class_name; ?></td>
                </tr>
                <tr>
                    <th>Week</th>
                    <td><?php echo $lesson_note->week_number; ?></td>
                    <th>Term</th>
                    <td><?php echo $lesson_note->term; ?></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td><?php echo date('d/m/Y', strtotime($lesson_note->lesson_date)); ?></td>
                    <th>Academic Year</th>
                    <td><?php echo $running_year; ?></td>
                </tr>
                <tr>
                    <th>Teacher</th>
                    <td colspan="3"><?php echo $lesson_note->teacher_name; ?></td>
                </tr>
            </table>
        </div>

        <!-- Lesson Title -->
        <div class="section">
            <div class="section-title">Lesson Title</div>
            <div class="content-box">
                <strong><?php echo $lesson_note->title; ?></strong>
            </div>
        </div>

        <!-- Curriculum Reference -->
        <?php if ($lesson_note->strand_name || $lesson_note->sub_strand_name || $lesson_note->content_standard_code): ?>
        <div class="section">
            <div class="section-title">Curriculum Reference</div>
            <table class="data-table">
                <?php if ($lesson_note->strand_name): ?>
                <tr>
                    <th style="width: 25%;">Strand</th>
                    <td><?php echo $lesson_note->strand_name; ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($lesson_note->sub_strand_name): ?>
                <tr>
                    <th>Sub-Strand</th>
                    <td><?php echo $lesson_note->sub_strand_name; ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($lesson_note->content_standard_code): ?>
                <tr>
                    <th>Content Standard</th>
                    <td><?php echo $lesson_note->content_standard_code; ?> - <?php echo $lesson_note->content_standard_description; ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>

        <!-- Learning Indicators -->
        <?php if (!empty($lesson_note->learning_indicators)): ?>
        <div class="section">
            <div class="section-title">Learning Indicators</div>
            <ul class="simple-list">
                <?php foreach ($lesson_note->learning_indicators as $indicator): ?>
                <li><strong><?php echo $indicator->code; ?>:</strong> <?php echo $indicator->description; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Core Competencies -->
        <?php if (!empty($lesson_note->core_competencies)): ?>
        <div class="section">
            <div class="section-title">Core Competencies</div>
            <div class="competency-tags">
                <?php foreach ($lesson_note->core_competencies as $competency): ?>
                <span class="competency-tag"><?php echo $competency->name; ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lesson Objectives -->
        <?php if ($lesson_note->lesson_objectives): ?>
        <div class="section">
            <div class="section-title">Lesson Objectives</div>
            <div class="content-box">
                <?php echo strip_tags($lesson_note->lesson_objectives); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Teaching and Learning Resources -->
        <?php if (!empty($lesson_note->resources)): ?>
        <div class="section">
            <div class="section-title">Teaching and Learning Resources (TLRs)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">S/N</th>
                        <th style="width: 50%;">Resource</th>
                        <th style="width: 15%;">Quantity</th>
                        <th style="width: 30%;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($lesson_note->resources as $resource): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $i++; ?></td>
                        <td><?php echo $resource->resource_name; ?></td>
                        <td style="text-align: center;"><?php echo $resource->quantity ?: '-'; ?></td>
                        <td><?php echo $resource->resource_details ?: '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Lesson Activities -->
        <?php if ($lesson_note->lesson_activities): ?>
        <div class="section">
            <div class="section-title">Lesson Activities</div>
            <div class="content-box">
                <?php echo strip_tags($lesson_note->lesson_activities); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Assessment Methods -->
        <?php if (!empty($lesson_note->assessments)): ?>
        <div class="section">
            <div class="section-title">Assessment Methods</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">S/N</th>
                        <th style="width: 35%;">Method</th>
                        <th style="width: 60%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($lesson_note->assessments as $assessment): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $i++; ?></td>
                        <td><?php echo $assessment->method_name; ?></td>
                        <td><?php echo $assessment->notes ?: '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Reference Materials -->
        <?php if (!empty($lesson_note->references)): ?>
        <div class="section">
            <div class="section-title">Reference Materials</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">S/N</th>
                        <th style="width: 35%;">Title</th>
                        <th style="width: 25%;">Author</th>
                        <th style="width: 20%;">Publisher/Year</th>
                        <th style="width: 15%;">Pages</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($lesson_note->references as $ref): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $i++; ?></td>
                        <td><?php echo $ref->title; ?></td>
                        <td><?php echo $ref->author ?: '-'; ?></td>
                        <td><?php echo trim(($ref->publisher ?: '') . ' ' . ($ref->year ? '(' . $ref->year . ')' : '')) ?: '-'; ?></td>
                        <td><?php echo $ref->page_numbers ?: '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-title">Teacher's Signature</div>
                    <div class="signature-date">Date: _______________</div>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-title">HOD's Signature</div>
                    <div class="signature-date">Date: _______________</div>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-title">Headmaster's Signature</div>
                    <div class="signature-date">Date: _______________</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Generated on <?php echo date('d/m/Y H:i'); ?> | <?php echo $school_name; ?> | Academic Year: <?php echo $running_year; ?></p>
        </div>
    </div>
</body>
</html>
