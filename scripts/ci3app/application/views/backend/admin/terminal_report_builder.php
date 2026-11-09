<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Terminal Report Builder</h1>
        <p class="text-gray-600 mt-2">Generate GES-compliant end-term reports with graphical presentation</p>
    </div>

    <!-- Student Selection -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Select Student</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                <select id="class_id" class="w-full border-gray-300 rounded-lg" onchange="loadStudents()">
                    <option value="">Select Class</option>
                    <?php
                    $classes = $this->db->get('class')->result();
                    foreach($classes as $class):
                    ?>
                    <option value="<?php echo $class->class_id; ?>"><?php echo $class->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Student</label>
                <select id="student_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">Select Student</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                <input type="text" id="year" class="w-full border-gray-300 rounded-lg" 
                       value="<?php echo $this->db->get_where('settings', ['type' => 'running_year'])->row()->description; ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Term</label>
                <select id="term" class="w-full border-gray-300 rounded-lg">
                    <option value="1">Term 1</option>
                    <option value="2">Term 2</option>
                    <option value="3">Term 3</option>
                </select>
            </div>
        </div>
        <button onclick="loadReport()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Load Report
        </button>
    </div>

    <!-- Report Content -->
    <div id="reportContent" class="hidden">
        <!-- Student Info -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="text-sm opacity-90">Student Name</div>
                    <div class="text-xl font-bold" id="studentName"></div>
                </div>
                <div>
                    <div class="text-sm opacity-90">Class</div>
                    <div class="text-xl font-bold" id="studentClass"></div>
                </div>
                <div>
                    <div class="text-sm opacity-90">Academic Year</div>
                    <div class="text-xl font-bold" id="academicYear"></div>
                </div>
            </div>
        </div>

        <!-- Scores Table & Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Scores Table -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-4">Subject Performance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Subject</th>
                                <th class="p-2 text-center">Portfolio</th>
                                <th class="p-2 text-center">SBA</th>
                                <th class="p-2 text-center">Exam</th>
                                <th class="p-2 text-center">Total</th>
                                <th class="p-2 text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody id="scoresTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-4">Graphical Presentation</h3>
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Attendance -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold mb-4">Attendance Record</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Days Present</label>
                    <input type="number" id="attendance_present" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Days</label>
                    <input type="number" id="attendance_total" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attendance %</label>
                    <input type="text" id="attendance_percent" class="w-full border-gray-300 rounded-lg bg-gray-100" readonly>
                </div>
            </div>
        </div>

        <!-- Conduct & Attitude -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold mb-4">Conduct & Attitude</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conduct</label>
                    <select id="conduct" class="w-full border-gray-300 rounded-lg">
                        <option value="Excellent">Excellent</option>
                        <option value="Very Good">Very Good</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Poor">Poor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attitude to Work</label>
                    <select id="attitude" class="w-full border-gray-300 rounded-lg">
                        <option value="Excellent">Excellent</option>
                        <option value="Very Good">Very Good</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Needs Improvement">Needs Improvement</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Interest</label>
                    <select id="interest" class="w-full border-gray-300 rounded-lg">
                        <option value="Very Keen">Very Keen</option>
                        <option value="Keen">Keen</option>
                        <option value="Moderate">Moderate</option>
                        <option value="Needs Encouragement">Needs Encouragement</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold mb-4">Remarks</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class Teacher's Remark</label>
                    <textarea id="teacher_remark" rows="3" class="w-full border-gray-300 rounded-lg" 
                              placeholder="Enter class teacher's remark..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Headmaster's Remark</label>
                    <textarea id="headmaster_remark" rows="3" class="w-full border-gray-300 rounded-lg" 
                              placeholder="Enter headmaster's remark..."></textarea>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <button onclick="generateReport()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center gap-2">
                <i class="fa fa-save"></i> Save Report
            </button>
            <button onclick="printReport()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <i class="fa fa-print"></i> Print Report
            </button>
            <button onclick="printBill()" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 flex items-center gap-2">
                <i class="fa fa-file-invoice-dollar"></i> Print Bill
            </button>
            <button onclick="bulkPrint()" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 flex items-center gap-2">
                <i class="fa fa-print"></i> Bulk Print Class
            </button>
            <button onclick="exportPDF()" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 flex items-center gap-2">
                <i class="fa fa-file-pdf"></i> Export PDF
            </button>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
let performanceChart = null;
let currentStudentId, currentYear, currentTerm;

function loadStudents() {
    const classId = document.getElementById('class_id').value;
    if(!classId) return;
    
    $.post('<?php echo site_url('admin/get_students_by_class'); ?>', {class_id: classId}, function(students) {
        const select = document.getElementById('student_id');
        select.innerHTML = '<option value="">Select Student</option>';
        students.forEach(s => {
            select.innerHTML += `<option value="${s.student_id}">${s.name}</option>`;
        });
    }, 'json');
}

function loadReport() {
    currentStudentId = document.getElementById('student_id').value;
    currentYear = document.getElementById('year').value;
    currentTerm = document.getElementById('term').value;
    
    if(!currentStudentId) {
        alert('Please select a student');
        return;
    }
    
    $.post('<?php echo site_url('terminal_report/get_student_report'); ?>', {
        student_id: currentStudentId,
        year: currentYear,
        term: currentTerm
    }, function(response) {
        if(response.status === 'success') {
            renderReport(response);
        }
    }, 'json');
}

function renderReport(data) {
    document.getElementById('reportContent').classList.remove('hidden');
    
    // Student info
    document.getElementById('studentName').textContent = data.student.name;
    document.getElementById('studentClass').textContent = data.student.class_name;
    document.getElementById('academicYear').textContent = currentYear + ' - Term ' + currentTerm;
    
    // Scores table
    let tableHTML = '';
    const chartLabels = [];
    const chartData = [];
    
    data.subjects.forEach(subject => {
        const total = parseFloat(subject.total_score || 0);
        const grade = getGrade(total);
        
        tableHTML += `
            <tr class="border-b hover:bg-gray-50">
                <td class="p-2">${subject.subject_name}</td>
                <td class="p-2 text-center">${subject.portfolio_score || '-'}</td>
                <td class="p-2 text-center">${subject.sba_score || '-'}</td>
                <td class="p-2 text-center">${subject.exam_score || '-'}</td>
                <td class="p-2 text-center font-bold">${total.toFixed(1)}</td>
                <td class="p-2 text-center"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">${grade}</span></td>
            </tr>
        `;
        
        chartLabels.push(subject.subject_name);
        chartData.push(total);
    });
    
    document.getElementById('scoresTable').innerHTML = tableHTML;
    
    // Chart
    renderChart(chartLabels, chartData);
    
    // Attendance
    document.getElementById('attendance_present').value = data.attendance.present_days || 0;
    document.getElementById('attendance_total').value = data.attendance.total_days || 0;
    calculateAttendance();
}

function renderChart(labels, data) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    if(performanceChart) {
        performanceChart.destroy();
    }
    
    performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Score',
                data: data,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
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
                    display: false
                }
            }
        }
    });
}

function calculateAttendance() {
    const present = parseFloat(document.getElementById('attendance_present').value) || 0;
    const total = parseFloat(document.getElementById('attendance_total').value) || 0;
    const percent = total > 0 ? ((present / total) * 100).toFixed(1) : 0;
    document.getElementById('attendance_percent').value = percent + '%';
}

document.getElementById('attendance_present').addEventListener('input', calculateAttendance);
document.getElementById('attendance_total').addEventListener('input', calculateAttendance);

function getGrade(score) {
    if(score >= 80) return 'A1';
    if(score >= 70) return 'B2';
    if(score >= 65) return 'B3';
    if(score >= 60) return 'C4';
    if(score >= 55) return 'C5';
    if(score >= 50) return 'C6';
    if(score >= 45) return 'D7';
    if(score >= 40) return 'E8';
    return 'F9';
}

function generateReport() {
    $.post('<?php echo site_url('terminal_report/generate_report'); ?>', {
        student_id: currentStudentId,
        year: currentYear,
        term: currentTerm,
        attendance_present: document.getElementById('attendance_present').value,
        attendance_total: document.getElementById('attendance_total').value,
        conduct: document.getElementById('conduct').value,
        attitude: document.getElementById('attitude').value,
        interest: document.getElementById('interest').value,
        teacher_remark: document.getElementById('teacher_remark').value,
        headmaster_remark: document.getElementById('headmaster_remark').value
    }, function(response) {
        alert(response.message);
    }, 'json');
}

function printReport() {
    window.open('<?php echo site_url('terminal_report/print_report'); ?>/' + currentStudentId + '/' + currentYear + '/' + currentTerm, '_blank');
}

function printBill() {
    if(!currentStudentId) {
        alert('Please select a student first');
        return;
    }
    window.open('<?php echo site_url('terminal_report/print_bill'); ?>/' + currentStudentId + '/' + currentYear + '/' + currentTerm, '_blank');
}

function bulkPrint() {
    const classId = document.getElementById('class_id').value;
    if(!classId) {
        alert('Please select a class first');
        return;
    }
    window.open('<?php echo site_url('terminal_report/bulk_print'); ?>?class_id=' + classId + '&year=' + currentYear + '&term=' + currentTerm, '_blank');
}

function exportPDF() {
    alert('PDF export feature coming soon');
}
</script>
