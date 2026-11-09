<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card Verification - <?php echo $school_name; ?></title>
    <link rel="icon" href="<?php echo base_url(); ?>uploads/logo.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            max-width: 600px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .school-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            background: white;
            border-radius: 50%;
            padding: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo img {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .verification-badge {
            text-align: center;
            margin-bottom: 30px;
        }

        .badge-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .badge-icon::before {
            content: "✓";
            color: white;
            font-size: 36px;
            font-weight: bold;
        }

        .verification-badge h2 {
            color: #10b981;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .verification-badge p {
            color: #6b7280;
            font-size: 14px;
        }

        .info-section {
            background: #f9fafb;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .info-section h3 {
            color: #374151;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
        }

        .footer-info {
            background: #f3f4f6;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.6;
        }

        .footer-info strong {
            color: #374151;
        }

        .contact-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-verified {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        @media (max-width: 600px) {
            body {
                padding: 0;
            }

            .container {
                border-radius: 0;
            }

            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 30px 20px;
            }

            .info-row {
                flex-direction: column;
                gap: 5px;
            }

            .info-value {
                text-align: left;
            }
        }

        .print-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        @media print {
            body {
                background: white;
            }

            .container {
                box-shadow: none;
            }

            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="school-logo">
                <img src="<?php echo base_url(); ?>uploads/school_logo.png" alt="School Logo" style="width:100px;height:100px;object-fit:contain;">
            </div>
            <h1><?php echo $school_name; ?></h1>
            <p>Report Card Verification System</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Verification Badge -->
            <div class="verification-badge">
                <div class="badge-icon"></div>
                <h2>Report Card Verified</h2>
                <p>This document is authentic and issued by <?php echo $school_name; ?></p>
            </div>

            <!-- Student Information -->
            <div class="info-section">
                <h3>Student Information</h3>
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-value"><?php echo $student->name; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Student Code:</span>
                    <span class="info-value"><?php echo $student->student_code; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Class:</span>
                    <span class="info-value">
                        <?php 
                            if (isset($class)) {
                                $class_display = $class->name;
                                if (!empty($class->name_numeric)) {
                                    $class_display .= ' ' . $class->name_numeric;
                                }
                                if (isset($section) && !empty($section->name)) {
                                    $class_display .= ' - ' . $section->name;
                                }
                                echo $class_display;
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </span>
                </div>
            </div>

            <!-- Exam Information -->
            <div class="info-section">
                <h3>Exam Information</h3>
                <div class="info-row">
                    <span class="info-label">Exam Name:</span>
                    <span class="info-value"><?php echo $exam->name; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Academic Year / Term:</span>
                    <span class="info-value"><?php echo $year; ?> - Term <?php echo isset($term) ? $term : get_settings('running_term'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Report Status:</span>
                    <span class="info-value">
                        <?php if ($marks_exist): ?>
                            <span class="status-badge status-verified">Published</span>
                        <?php else: ?>
                            <span class="status-badge status-pending">Processing</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Academic Performance -->
            <?php if ($marks_exist): ?>
            <div class="info-section">
                <h3>Academic Performance</h3>
                
                <!-- Overall Summary -->
                <div style="background: #f0f9ff; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #0284c7;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 3px;">Total Score</div>
                            <div style="font-size: 24px; font-weight: 700; color: #0284c7;">
                                <?php echo $total_marks; ?> / <?php echo $total_possible; ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 3px;">Avg. Percentage</div>
                            <div style="font-size: 24px; font-weight: 700; color: <?php echo $percentage >= 50 ? '#10b981' : '#ef4444'; ?>;">
                                <?php echo $percentage; ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subject Breakdown -->
                <div style="margin-top: 15px;">
                    <div style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 10px;">Subject Breakdown:</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f3f4f6;">
                                <th style="padding: 10px; text-align: left; font-size: 12px; color: #6b7280; font-weight: 600; border-bottom: 2px solid #e5e7eb;">Subject</th>
                                <th style="padding: 10px; text-align: right; font-size: 12px; color: #6b7280; font-weight: 600; border-bottom: 2px solid #e5e7eb;">Score%</th>
                                <!-- <th style="padding: 10px; text-align: center; font-size: 12px; color: #6b7280; font-weight: 600; border-bottom: 2px solid #e5e7eb;">%</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marks as $mark): 
                                $subject_percentage = $mark->mark_total > 0 ? round($mark->mark_obtained, 1) : 0;
                                $color = $subject_percentage >= 50 ? '#10b981' : '#ef4444';
                            ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 10px; font-size: 13px; color: #374151;"><?php echo $mark->subject_name; ?></td>
                                <td style="padding: 10px; text-align: right; font-size: 13px; font-weight: 600; color: #111827;">
                                    <?php echo $mark->mark_obtained; ?>%
                                </td>
                                <!-- <td style="padding: 10px; text-align: center; font-size: 13px; font-weight: 600; color: <?php //echo $color; ?>;">
                                    <?php //echo $subject_percentage; ?>%
                                </td> -->
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="info-section" style="text-align: center; padding: 30px;">
                <div style="font-size: 48px; margin-bottom: 10px;">⏳</div>
                <div style="font-size: 16px; font-weight: 600; color: #6b7280; margin-bottom: 5px;">Marks Processing</div>
                <div style="font-size: 14px; color: #9ca3af;">Results will be available soon. Please check back later.</div>
            </div>
            <?php endif; ?>

            <!-- Verification Details -->
            <div class="info-section">
                <h3>Verification Details</h3>
                <div class="info-row">
                    <span class="info-label">Verified On:</span>
                    <span class="info-value"><?php echo $verification_time; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Verification Method:</span>
                    <span class="info-value">QR Code Scan</span>
                </div>
            </div>

            <button class="print-btn" onclick="window.print()">Print Verification</button>
        </div>

        <!-- Footer -->
        <div class="footer-info">
            <strong>Important Notice:</strong><br>
            This is an automated verification system. The information displayed is retrieved directly from the school's official records.
            
            <div class="contact-info">
                <strong>Contact Information:</strong><br>
                <?php echo $school_address; ?><br>
                Phone: <?php echo $school_phone; ?><br>
                Email: <?php echo $school_email; ?>
            </div>
        </div>
    </div>
</body>
</html>
