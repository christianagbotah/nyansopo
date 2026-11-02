<?php
/**
 * Bulk Cumulative Reports — printable, one compact sheet per student in class
 * Data: $selected_class_id
 * Queries enrolled students (running year/term) and renders each student's
 * subject × terminal-exam mark grid with totals, averages and grading.
 */
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$location    = $this->db->get_where('settings', array('type' => 'location'))->row()->description;
$address     = $this->db->get_where('settings', array('type' => 'address'))->row()->description;
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');

$grading = $this->db->order_by('mark_from', 'DESC')->get('grade')->result_array();

$class_row = $this->db->get_where('class', array('class_id' => $selected_class_id))->row();
$class_label = $class_row ? ($class_row->name . ' ' . $class_row->name_numeric) : 'Class #' . $selected_class_id;

// All terminal exams in the running year (for the mark grid columns)
$exams = $this->db->where('year', $running_year)->order_by('exam_id', 'ASC')->get('exam')->result_array();

// Enrolled students for this class / running year+term
$students = $this->db->select('s.*, e.section_id')
    ->from('student s')
    ->join('enroll e', 'e.student_id = s.student_id')
    ->where('e.class_id', $selected_class_id)
    ->where('e.year', $running_year)
    ->where('e.term', $running_term)
    ->where('e.mute', '0')
    ->order_by('s.name', 'ASC')
    ->get()
    ->result_array();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Cumulative Reports — <?php echo htmlspecialchars($class_label); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12px; color: #111827; background: #e5e7eb; }
        .sheet { max-width: 980px; margin: 20px auto; }
        .page {
            background: #fff; border-radius: 8px; box-shadow: 0 6px 18px rgba(0,0,0,.12);
            padding: 22px 26px; margin-bottom: 22px; page-break-after: always;
        }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #111827; padding-bottom: 12px; margin-bottom: 14px; }
        .head .school { font-size: 16px; font-weight: 800; letter-spacing: .5px; }
        .head .sub { font-size: 10.5px; color: #6b7280; margin-top: 3px; }
        .badge-term { background: #111827; color: #fff; border-radius: 8px; font-size: 11px; letter-spacing: 2px; padding: 5px 13px; }
        .stu { display: flex; gap: 14px; align-items: center; margin-bottom: 12px; }
        .stu img { width: 64px; height: 64px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb; }
        .stu .nm { font-size: 15px; font-weight: 800; }
        .stu .cd { font-size: 11px; color: #6b7280; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #374151; color: #fff; padding: 6px 9px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
        th.r, td.r { text-align: right; }
        td { padding: 6px 9px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        tr.avg td { background: #eef2ff; font-weight: 800; color: #3730a3; }
        .grading { display: flex; gap: 8px; flex-wrap: wrap; margin: 10px 0; }
        .g-chip { font-size: 10px; font-weight: 700; border: 1px solid #e5e7eb; border-radius: 20px; padding: 4px 10px; color: #374151; }
        .signs { display: flex; justify-content: space-between; margin-top: 34px; }
        .sign { border-top: 1.5px solid #9ca3af; padding-top: 5px; font-size: 10.5px; color: #6b7280; width: 170px; text-align: center; }
        .toolbar { text-align: center; padding: 12px; }
        .toolbar button { background: #111827; color: #fff; border: none; border-radius: 8px; padding: 11px 26px; font-size: 14px; font-weight: 700; cursor: pointer; }
        .empty { text-align: center; padding: 50px; color: #6b7280; font-weight: 700; background: #fff; border-radius: 10px; }
        @media print { body { background: #fff; } .sheet { max-width: 100%; } .page { box-shadow: none; border-radius: 0; margin: 0; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()"><i class="fa fa-print"></i> Print All Report Sheets</button></div>

    <div class="sheet">
        <?php if (empty($students)): ?>
        <div class="empty">No students enrolled in <?php echo htmlspecialchars($class_label); ?> for <?php echo htmlspecialchars($running_term . ' / ' . $running_year); ?>.</div>
        <?php else: foreach ($students as $st): ?>

        <?php
        // Subject rows for this student in this class across the year's terminal exams
        $subjects = $this->db->select('sub.name as subject_name, sub.subject_id')
            ->from('mark m')
            ->join('subject sub', 'sub.subject_id = m.subject_id')
            ->where('m.student_id', $st['student_id'])
            ->where('m.class_id', $selected_class_id)
            ->where('m.year', $running_year)
            ->group_by('m.subject_id')
            ->order_by('sub.name', 'ASC')
            ->get()
            ->result_array();

        // marks[subject_id][exam_id] = mark_obtained
        $marks = array();
        $mk_rows = $this->db->select('subject_id, exam_id, mark_obtained')
            ->where('student_id', $st['student_id'])
            ->where('class_id', $selected_class_id)
            ->where('year', $running_year)
            ->get('mark')
            ->result_array();
        foreach ($mk_rows as $mk) $marks[$mk['subject_id']][$mk['exam_id']] = (float)$mk['mark_obtained'];

        $grand_total = 0; $grand_count = 0;
        ?>

        <div class="page">
            <div class="head">
                <div>
                    <div class="school"><?php echo htmlspecialchars($system_name); ?></div>
                    <div class="sub"><?php echo htmlspecialchars($location . ' · ' . $address); ?></div>
                    <div class="sub" style="margin-top:4px;"><strong>CUMULATIVE REPORT SHEET — <?php echo htmlspecialchars($class_label); ?></strong></div>
                </div>
                <div class="badge-term"><?php echo htmlspecialchars($running_year); ?></div>
            </div>

            <div class="stu">
                <img src="<?php echo $this->crud_model->get_image_url('student', $st['student_id'], $st['sex']); ?>" alt="">
                <div>
                    <div class="nm"><?php echo htmlspecialchars($st['name']); ?></div>
                    <div class="cd">Student Code: <?php echo htmlspecialchars($st['student_code'] ?: '—'); ?> · Class: <?php echo htmlspecialchars($class_label); ?></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <?php foreach ($exams as $ex): ?>
                        <th class="r"><?php echo htmlspecialchars(mb_strimwidth($ex->name, 0, 18, '…')); ?></th>
                        <?php endforeach; ?>
                        <th class="r">Total</th>
                        <th class="r">Average</th>
                        <th class="r">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                    <tr><td colspan="<?php echo count($exams) + 4; ?>" style="text-align:center;padding:26px;color:#6b7280;">No terminal marks recorded for this student.</td></tr>
                    <?php else: foreach ($subjects as $sub):
                        $row_total = 0; $row_count = 0; ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                        <?php foreach ($exams as $ex):
                            $v = isset($marks[$sub['subject_id']][$ex->exam_id]) ? $marks[$sub['subject_id']][$ex->exam_id] : null;
                            if ($v !== null) { $row_total += $v; $row_count++; } ?>
                        <td class="r"><?php echo $v !== null ? number_format($v, 1) : '—'; ?></td>
                        <?php endforeach; ?>
                        <td class="r" style="font-weight:700;"><?php echo number_format($row_total, 1); ?></td>
                        <td class="r"><?php echo $row_count ? number_format($row_total / $row_count, 1) : '—'; ?></td>
                        <?php
                        $avg = $row_count ? $row_total / $row_count : 0;
                        $grade_label = '—';
                        foreach ($grading as $g) {
                            if ($avg >= (float)$g['mark_from'] && $avg <= (float)$g['mark_upto']) { $grade_label = $g['name']; break; }
                        }
                        ?>
                        <td class="r"><?php echo htmlspecialchars($grade_label); ?></td>
                    </tr>
                    <?php $grand_total += $row_total; $grand_count += $row_count; endforeach; ?>
                    <tr class="avg">
                        <td>OVERALL</td>
                        <?php foreach ($exams as $ex):
                            $col = 0;
                            foreach ($subjects as $sub) {
                                $col += isset($marks[$sub['subject_id']][$ex->exam_id]) ? $marks[$sub['subject_id']][$ex->exam_id] : 0;
                            } ?>
                        <td class="r"><?php echo number_format($col, 1); ?></td>
                        <?php endforeach; ?>
                        <td class="r"><?php echo number_format($grand_total, 1); ?></td>
                        <td class="r"><?php echo $grand_count ? number_format($grand_total / $grand_count, 1) : '—'; ?></td>
                        <td class="r"><?php echo htmlspecialchars($grade_label); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="grading">
                <?php foreach ($grading as $g): ?>
                <span class="g-chip"><?php echo htmlspecialchars($g['mark_from'] . '-' . $g['mark_upto'] . '% ' . ucwords(strtolower($g['name']))); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="signs">
                <div class="sign">Class Teacher</div>
                <div class="sign">Headteacher</div>
                <div class="sign">Parent / Guardian</div>
            </div>
        </div>

        <?php endforeach; endif; ?>
    </div>
</body>
</html>
