<?php
$running_year_query = $this->db->get_where('settings', array('type' => 'running_year'));
$running_year = $running_year_query->num_rows() > 0 ? $running_year_query->row()->description : date('Y');

$this->db->select('s.student_id, s.name, s.student_code, s.sex, s.address, e.class_id, c.name as class_name');
$this->db->from('student s');
$this->db->join('enroll e', 's.student_id = e.student_id', 'left');
$this->db->join('class c', 'e.class_id = c.class_id', 'left');
$this->db->where('s.special_diet', '1');
if($running_year) {
    $this->db->where('e.year', $running_year);
}
$this->db->group_by('s.student_id');
$this->db->order_by('s.name', 'ASC');
$students = $this->db->get()->result_array();

$total_students = count($students);
?>

<style>
.special-diet-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    color: white;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    margin: 0;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
    margin-top: 5px;
}

.student-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e8e8e8;
}

.student-card:hover {
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
    transform: translateY(-2px);
}

.student-avatar {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    object-fit: cover;
    border: 3px solid #f0f0f0;
}

.student-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
}

.student-meta {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #7f8c8d;
}

.meta-item i {
    color: #667eea;
}

.diet-badge {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.action-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-view {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.empty-state img {
    width: 120px;
    opacity: 0.5;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.empty-state p {
    color: #7f8c8d;
}

.search-box {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 500px;
}

.search-input {
    width: 100%;
    padding: 12px 16px 12px 45px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 14px;
    color: #7f8c8d;
}
</style>

<div class="special-diet-header">
    <div class="row">
        <div class="col-md-8">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-cutlery" style="font-size: 32px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-weight: 700; font-size: 28px; color: white;"><?php echo get_phrase('students_on_special_diet'); ?></h2>
                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 15px; color: white;">Students requiring special dietary considerations</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <p class="stat-number"><?php echo $total_students; ?></p>
                <p class="stat-label">Total Students</p>
            </div>
        </div>
    </div>
</div>

<div class="search-box">
    <div style="position: relative;">
        <i class="fa fa-search search-icon"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Search by name, ID, class, or address...">
    </div>
</div>

<div id="studentsContainer">
    <?php if($total_students > 0): ?>
        <?php foreach($students as $student): ?>
        <div class="student-card" data-search="<?php echo strtolower($student['name'] . ' ' . $student['student_code'] . ' ' . ($student['class_name'] ?? '') . ' ' . ($student['address'] ?? '')); ?>">
            <div class="row">
                <div class="col-md-8">
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <img src="<?php echo $this->crud_model->get_image_url('student', $student['student_id'], $student['sex']); ?>" 
                             class="student-avatar" 
                             alt="<?php echo $student['name']; ?>">
                        <div class="student-info">
                            <h4><?php echo $student['name']; ?></h4>
                            <div class="student-meta">
                                <span class="meta-item">
                                    <i class="fa fa-id-card"></i>
                                    <span><?php echo $student['student_code']; ?></span>
                                </span>
                                <?php if(!empty($student['class_name'])): ?>
                                <span class="meta-item">
                                    <i class="fa fa-graduation-cap"></i>
                                    <span><?php echo $student['class_name']; ?></span>
                                </span>
                                <?php endif; ?>
                                <?php if(!empty($student['address'])): ?>
                                <span class="meta-item">
                                    <i class="fa fa-map-marker"></i>
                                    <span><?php echo $student['address']; ?></span>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                    <span class="diet-badge">
                        <i class="fa fa-cutlery"></i>
                        Special Diet
                    </span>
                    <a href="<?php echo site_url('admin/student_profile/' . $student['student_id']); ?>" 
                       class="action-btn btn-view">
                        <i class="fa fa-eye"></i> View Profile
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-cutlery" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
            <h3>No Students on Special Diet</h3>
            <p>There are currently no students requiring special dietary considerations.</p>
        </div>
    <?php endif; ?>
</div>

<div id="noResults" style="display: none;">
    <div class="empty-state">
        <i class="fa fa-search" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
        <h3>No Results Found</h3>
        <p>Try adjusting your search terms.</p>
    </div>
</div>

<script>
$(document).ready(function() {
    // Search functionality
    $('#searchInput').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        var visibleCount = 0;
        
        $('.student-card').each(function() {
            var searchData = $(this).data('search');
            if(searchData.indexOf(searchTerm) > -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        if(visibleCount === 0 && searchTerm !== '') {
            $('#noResults').show();
        } else {
            $('#noResults').hide();
        }
    });
});
</script>
