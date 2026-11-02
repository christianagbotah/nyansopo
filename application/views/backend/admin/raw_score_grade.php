<?php
// Auto-populate WAEC grades if table is empty
$grade_count = $this->db->count_all('raw_score_grade');
if ($grade_count == 0) {
    $waec_grades = [
        ['name' => 'EXCELLENT', 'grade_point' => 'A1', 'mark_from' => 80, 'mark_upto' => 100],
        ['name' => 'VERY GOOD', 'grade_point' => 'B2', 'mark_from' => 70, 'mark_upto' => 79],
        ['name' => 'GOOD', 'grade_point' => 'B3', 'mark_from' => 65, 'mark_upto' => 69],
        ['name' => 'CREDIT', 'grade_point' => 'C4', 'mark_from' => 60, 'mark_upto' => 64],
        ['name' => 'CREDIT', 'grade_point' => 'C5', 'mark_from' => 55, 'mark_upto' => 59],
        ['name' => 'CREDIT', 'grade_point' => 'C6', 'mark_from' => 50, 'mark_upto' => 54],
        ['name' => 'PASS', 'grade_point' => 'D7', 'mark_from' => 45, 'mark_upto' => 49],
        ['name' => 'PASS', 'grade_point' => 'E8', 'mark_from' => 40, 'mark_upto' => 44],
        ['name' => 'FAIL', 'grade_point' => 'F9', 'mark_from' => 0, 'mark_upto' => 39]
    ];
    $this->db->insert_batch('raw_score_grade', $waec_grades);
}

$terminal_report_style = $this->db->get_where('settings', array('type'=>'terminal_report_style'))->row()->description;
if($terminal_report_style == 'style_1') {
    $grades = $this->db->get('raw_score_grade')->result_array();
} else {
    $grades = $this->db->get('raw_score_grade')->result_array();
}
?>

<style>
.grade-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.grade-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.grade-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.grade-table {
    width: 100%;
    border-collapse: collapse;
}

.grade-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.grade-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border: none;
}

.grade-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.grade-table tbody tr:hover {
    background-color: #f8fafc;
}

.grade-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
    text-align: center;
    min-width: 60px;
}

.grade-a { background: #dcfce7; color: #166534; }
.grade-b { background: #dbeafe; color: #1e40af; }
.grade-c { background: #fef3c7; color: #92400e; }
.grade-d { background: #fed7d7; color: #c53030; }
.grade-f { background: #fecaca; color: #dc2626; }

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-edit {
    background: #10b981;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-edit:hover {
    background: #059669;
    transform: translateY(-1px);
    color: white;
}

.btn-delete {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-delete:hover {
    background: #dc2626;
    transform: translateY(-1px);
    color: white;
}

.add-grade-form {
    background: white;
    padding: 3rem;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    margin: 3rem auto;
    max-width: 900px;
    border: 1px solid #e5e7eb;
}

.form-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #f3f4f6;
}

.form-header h2 {
    margin: 0 0 1rem 0;
    color: #1f2937;
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.form-header p {
    margin: 0;
    color: #6b7280;
    font-size: 1.1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2.5rem;
    margin-bottom: 3rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    position: relative;
}

.form-label {
    font-weight: 700;
    color: #374151;
    margin-bottom: 0.75rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label i {
    color: #667eea;
    font-size: 1.1rem;
}

.form-input {
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fafbfc;
    font-weight: 500;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    background: white;
    transform: translateY(-2px);
}

.form-input::placeholder {
    color: #9ca3af;
    font-weight: 400;
}

.form-actions {
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    padding-top: 2rem;
    border-top: 2px solid #f3f4f6;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 2.5rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 160px;
    justify-content: center;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 2px solid #e5e7eb;
    padding: 1rem 2.5rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 160px;
    justify-content: center;
}

.btn-secondary:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.toggle-form {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 50%;
    width: 70px;
    height: 70px;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-form:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
}

.toggle-form i {
    font-size: 24px;
    font-weight: 900;
}

.form-slide-in {
    animation: slideInUp 0.5s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-hidden {
    display: none;
}

.range-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .grade-stats {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .add-grade-form {
        margin: 1rem;
        padding: 2rem;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .form-header h2 {
        font-size: 2rem;
    }
}
</style>

<div class="grade-header">
    <h1 style="margin: 0; font-size: 2rem; font-weight: 700; color: white;">
        <i class="fa fa-graduation-cap" style="margin-right: 1rem;"></i>
        Raw Score Grading System
    </h1>
    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; color: white;">Configure and manage raw score grading scales</p>
</div>

<div class="grade-stats">
    <div class="stat-card">
        <h3 style="margin: 0 0 0.5rem 0; color: #667eea;">Total Grades</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: 700; color: #1f2937;"><?php echo count($grades); ?></p>
    </div>
    <div class="stat-card">
        <h3 style="margin: 0 0 0.5rem 0; color: #667eea;">Highest Grade</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: 700; color: #1f2937;">
            <?php echo !empty($grades) ? max(array_column($grades, 'mark_upto')) : '0'; ?>%
        </p>
    </div>
    <div class="stat-card">
        <h3 style="margin: 0 0 0.5rem 0; color: #667eea;">Lowest Grade</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: 700; color: #1f2937;">
            <?php echo !empty($grades) ? min(array_column($grades, 'mark_from')) : '0'; ?>%
        </p>
    </div>
    <div class="stat-card">
        <h3 style="margin: 0 0 0.5rem 0; color: #667eea;">Pass Mark</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: 700; color: #1f2937;">
            <?php 
            $pass_grades = array_filter($grades, function($g) { return $g['mark_from'] >= 50; });
            echo !empty($pass_grades) ? min(array_column($pass_grades, 'mark_from')) : '50';
            ?>%
        </p>
    </div>
</div>

<div class="grade-table-container">
    <table class="grade-table" id="table_export">
        <thead>
            <tr>
                <th>#</th>
                <th>Grade Name</th>
                <th>Grade</th>
                <th>Mark Range</th>
                <th>GPA</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $count = 1; foreach($grades as $row): ?>
            <tr>
                <td><?php echo $count++; ?></td>
                <td>
                    <strong><?php echo $row['name']; ?></strong>
                </td>
                <td>
                    <span class="grade-badge <?php 
                        $grade = strtolower($row['grade_point'][0] ?? 'f');
                        echo 'grade-' . $grade;
                    ?>">
                        <?php echo $row['grade_point']; ?>
                    </span>
                </td>
                <td>
                    <div class="range-display">
                        <span><?php echo $row['mark_from']; ?>%</span>
                        <span>-</span>
                        <span><?php echo $row['mark_upto']; ?>%</span>
                    </div>
                </td>
                <td>
                    <strong><?php echo $row['grade_point_numeric'] ?? 'N/A'; ?></strong>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="#" class="btn-edit" onclick="editGrade(<?php echo $row['grade_id']; ?>)">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="#" class="btn-delete" onclick="deleteGrade(<?php echo $row['grade_id']; ?>)">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="add-grade-form form-hidden" id="addGradeForm">
    <div class="form-header">
        <h2><i class="fa fa-graduation-cap"></i> Create New Raw Score Grade</h2>
        <p>Define a new raw score grading scale with mark ranges</p>
    </div>
    
    <?php echo form_open(site_url('admin/grade/create_raw'), array('id' => 'gradeForm')); ?>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">
                <i class="fa fa-tag"></i>
                Grade Name
            </label>
            <input type="text" name="name" class="form-input" placeholder="e.g., EXCELLENT, VERY GOOD" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fa fa-certificate"></i>
                Grade Symbol
            </label>
            <input type="text" name="grade_point" class="form-input" placeholder="e.g., A+, A, B+, 1, 2" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fa fa-arrow-up"></i>
                Minimum Mark (%)
            </label>
            <input type="number" name="mark_from" class="form-input" placeholder="e.g., 80.5" min="0" max="100" step="0.1" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fa fa-arrow-down"></i>
                Maximum Mark (%)
            </label>
            <input type="number" name="mark_upto" class="form-input" placeholder="e.g., 100" min="0" max="100" step="0.1" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fa fa-star"></i>
                GPA Points
            </label>
            <input type="number" name="gpa" class="form-input" placeholder="e.g., 4.0, 3.5, 3.0" step="0.1" min="0" max="5" required>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="fa fa-plus-circle"></i>
            Create Grade
        </button>
        <button type="button" class="btn-secondary" onclick="toggleForm()">
            <i class="fa fa-times-circle"></i>
            Cancel
        </button>
    </div>
    </form>
</div>

<button class="toggle-form" onclick="toggleForm()" id="toggleBtn">
    <i class="fa fa-plus"></i>
</button>

<script>
function toggleForm() {
    const form = document.getElementById('addGradeForm');
    const btn = document.getElementById('toggleBtn');
    
    if (form.classList.contains('form-hidden')) {
        form.classList.remove('form-hidden');
        form.classList.add('form-slide-in');
        btn.innerHTML = '<i class="fa fa-times"></i>';
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        form.classList.add('form-hidden');
        form.classList.remove('form-slide-in');
        btn.innerHTML = '<i class="fa fa-plus"></i>';
    }
}

function editGrade(id) {
    showAjaxModal('<?php echo site_url("modal/popup/modal_edit_raw_score_grade/"); ?>' + id);
}

function deleteGrade(id) {
    showConfirmModal(
        'Confirm Delete',
        'Are you sure you want to delete this grade? This action cannot be undone.',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/grade/delete_raw/"); ?>' + id,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.message === 'done') {
                    showAjaxModal_alert('Grade deleted successfully', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert('Failed to delete grade', 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Delete',
        'danger'
    );
}

// Form submission with AJAX
$('#gradeForm').submit(function(e) {
    e.preventDefault();
    
    // Hide the form first
    toggleForm();
    
    showAjaxModal_alert('Creating grade...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});

// Initialize DataTable
$(document).ready(function() {
    $('#table_export').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[2, 'asc']],
        columnDefs: [
            { orderable: false, targets: [4] }
        ]
    });
});
</script>