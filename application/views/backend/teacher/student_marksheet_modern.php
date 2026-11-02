<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('student_marksheet');?></h2>
        <p class="text-gray-600"><?php echo get_phrase('view_student_exam_results');?></p>
    </div>

    <!-- Student Selector -->
    <div class="mb-6 flex justify-end">
        <div class="w-full md:w-1/2">
            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('select_student');?></label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" id="other_students">
            <?php foreach($other_students as $os): ?>
                <option value="<?=$os['student_id']?>" <?=$student_id == $os['student_id'] ? 'selected' : ''; ?>>
                    <?=$this->crud_model->getStudentNameById($os['student_id'])?>
                </option>
            <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php 
    $class_name = $this->crud_model->get_class_name($class_id);
    $student_info = $this->crud_model->get_student_info($student_id);
    
    foreach ($student_info as $row1):
    ?>

    <!-- Results Table -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $this->crud_model->get_exams_name($exam_id);?></h3>
        
        <div class="overflow-x-auto">
            <table class="w-full bg-white rounded-lg overflow-hidden">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-4 py-3 text-center">S/N</th>
                        <th class="px-4 py-3 text-center">SUBJECT</th>
                        <th class="px-4 py-3 text-center">CLASS SCORE</th>
                        <th class="px-4 py-3 text-center">EXAM SCORE</th>
                        <th class="px-4 py-3 text-center">TOTAL SCORE</th>
                        <th class="px-4 py-3 text-center">GRADE</th>
                        <th class="px-4 py-3 text-center">REMARK</th>
                        <th class="px-4 py-3 text-center">POSITION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <?php
                    $class_score_total = 0;
                    $exam_score_total = 0; 
                    $total_marks = 0;
                    
                    $term_or_sem = ($class_name == 'JHSS') ? 'sem' : 'term';
                    $term_or_sem_value = ($class_name == 'JHSS') ? $running_sem : $running_term;
                    
                    $subjects = $this->db->get_where('subject', array(
                        'class_id' => $class_id,
                        'year' => $running_year,
                        $term_or_sem => $term_or_sem_value
                    ))->result_array();

                    $sn = 1;
                    foreach ($subjects as $row3):
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-center"><?php echo $sn;?></td>
                        <td class="px-4 py-3 font-medium"><?php echo strlen($row3['name']) <= 4 ? strtoupper($row3['name']) : $row3['name'];?></td>
                        <td class="px-4 py-3 text-center">
                            <?php
                                $class_score_query = $this->db->get_where('mark', array(
                                    'subject_id' => $row3['subject_id'],
                                    'exam_id' => $exam_id,
                                    'class_id' => $class_id,
                                    'student_id' => $student_id,
                                    'year' => $running_year,
                                    $term_or_sem => $term_or_sem_value
                                ));
                                if ($class_score_query->num_rows() > 0) {
                                    $class_score = $class_score_query->result_array();
                                    foreach ($class_score as $row4) {
                                        echo round($row4['class_score'], 2);
                                        $class_score_total += $row4['class_score'];
                                        $total_marks += $row4['class_score'];
                                    }
                                } else {
                                    echo "N/A";
                                }
                            ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php
                                $exam_score_query = $this->db->get_where('mark', array(
                                    'subject_id' => $row3['subject_id'],
                                    'exam_id' => $exam_id,
                                    'class_id' => $class_id,
                                    'student_id' => $student_id,
                                    'year' => $running_year,
                                    $term_or_sem => $term_or_sem_value
                                ));
                                if ($exam_score_query->num_rows() > 0) {
                                    $exam_score = $exam_score_query->result_array();
                                    foreach ($exam_score as $row4) {
                                        echo round($row4['exam_score'], 2);
                                        $exam_score_total += $row4['exam_score'];
                                        $total_marks += $row4['exam_score'];
                                    }
                                } else {
                                    echo "N/A";
                                }
                            ?>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600">
                            <?php
                                $obtained_mark_query = $this->db->get_where('mark', array(
                                    'subject_id' => $row3['subject_id'],
                                    'exam_id' => $exam_id,
                                    'class_id' => $class_id,
                                    'student_id' => $student_id,
                                    'year' => $running_year,
                                    $term_or_sem => $term_or_sem_value
                                ));
                                if ($obtained_mark_query->num_rows() > 0) {
                                    $marks = $obtained_mark_query->result_array();
                                    foreach ($marks as $row4) {
                                        echo round($row4['mark_obtained'], 2);
                                    }
                                } else {
                                    echo "N/A";
                                }
                            ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php
                                if($obtained_mark_query->num_rows() > 0) {
                                    if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                        $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                        echo '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">'.$grade['grade_point'].'</span>';
                                    }
                                } else {
                                    echo "N/A";
                                }
                            ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php
                                if($obtained_mark_query->num_rows() > 0) {
                                    if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                        $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                        echo $grade['name'];
                                    }
                                } else {
                                    echo "N/A";
                                }
                            ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php $this->crud_model->get_total_score($exam_id, $class_id, $row3['subject_id'], $row1['student_id'], $running_year, $term_or_sem_value); ?>
                        </td>
                    </tr>
                <?php 
                    $sn++;
                    endforeach;
                ?>
                    <tr class="bg-gray-100 font-bold">
                        <th colspan="2" class="px-4 py-3 text-center">TOTAL</th>
                        <td class="px-4 py-3 text-center"><?php echo $class_score_total ? round($class_score_total, 2) : 'N/A'; ?></td>
                        <td class="px-4 py-3 text-center"><?php echo $exam_score_total ? round($exam_score_total, 2) : 'N/A'; ?></td>
                        <td class="px-4 py-3 text-center text-blue-600"><?php echo $total_marks ? round($total_marks, 2) : 'N/A'; ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <h4 class="text-lg font-bold text-gray-800 mb-4"><?php echo get_phrase('performance_chart');?></h4>
        <canvas id="performanceChart" class="w-full" style="max-height: 400px;"></canvas>
    </div>

    <!-- Print Button -->
    <div class="flex justify-end">
        <a href="<?php echo site_url('admin/student_marksheet_print_view/'.$student_id.'/'.$exam_id);?>" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition flex items-center gap-2" 
           target="_blank">
            <i class="entypo-print"></i> <?php echo get_phrase('print_marksheet');?>
        </a>
    </div>

    <?php endforeach; ?>
</div>

<script>
// Student selector change
$('#other_students').change(function() {
    const url = '<?php echo site_url('admin/student_marksheet/') ?>' + $(this).val();
    navigation(url);
});

// Performance Chart
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [
            <?php 
            foreach ($subjects as $subject):
                echo "'".substr($subject['name'], 0, 10)."',";
            endforeach;
            ?>
        ],
        datasets: [{
            label: 'Class Score',
            data: [
                <?php 
                foreach ($subjects as $subject):
                    $class_mark = $this->crud_model->get_class_score($exam_id, $class_id, $subject['subject_id'], $row1['student_id']);
                    echo $class_mark.',';
                endforeach;
                ?>
            ],
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1
        }, {
            label: 'Exam Score',
            data: [
                <?php 
                foreach ($subjects as $subject):
                    $exam_mark = $this->crud_model->get_exam_score($exam_id, $class_id, $subject['subject_id'], $row1['student_id']);
                    echo $exam_mark.',';
                endforeach;
                ?>
            ],
            backgroundColor: 'rgba(99, 102, 241, 0.8)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        },
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Class Score vs Exam Score'
            }
        }
    }
});
</script>
