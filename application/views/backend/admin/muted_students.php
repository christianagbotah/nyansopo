<?php
$running_year_query = $this->db->get_where('settings', array('type' => 'running_year'));
$running_year = $running_year_query->num_rows() > 0 ? $running_year_query->row()->description : date('Y');

$this->db->select('s.student_id, s.name, s.student_code, s.sex, s.address, s.email, s.mute, s.block_limit, e.class_id, e.section_id, e.year, e.term, c.name as class_name, c.name_numeric as class_name_numeric, sec.name as section_name');
$this->db->from('student s');
$this->db->join('enroll e', 's.student_id = e.student_id', 'left');
$this->db->join('class c', 'e.class_id = c.class_id', 'left');
$this->db->join('section sec', 'e.section_id = sec.section_id', 'left');
$this->db->where('s.mute', '1');
// Get all muted students regardless of year/term - don't filter by running year
$this->db->group_by('s.student_id');
$this->db->order_by('s.name', 'ASC');
$students_raw = $this->db->get()->result_array();

// Build full class names for each student
$students = array();
foreach($students_raw as $student) {
    if(!empty($student['class_id'])) {
        $student['full_class_name'] = $student['class_name'] . ' ' . $student['class_name_numeric'] . (!empty($student['section_name']) ? ' ' . $student['section_name'] : '');
    } else {
        $student['full_class_name'] = '';
    }
    $students[] = $student;
}

$total_students = count($students);
?>

<style>
.muted-header {
    background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(127, 140, 141, 0.2);
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
    font-size: 13px;
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
    box-shadow: 0 8px 24px rgba(240, 147, 251, 0.15);
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
    color: #f5576c;
}

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-muted {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
}

.badge-blocked {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
}

.action-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-unmute {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.btn-unmute:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
    color: white;
}

.btn-unblock {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-unblock:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    color: white;
}

.btn-view {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-view:hover {
    background: #667eea;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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
    border-color: #f5576c;
    outline: none;
    box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 14px;
    color: #7f8c8d;
}
</style>

<div class="muted-header">
    <div class="row">
        <div class="col-md-6">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-user-times" style="font-size: 32px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-weight: 700; font-size: 28px; color: white;"><?php echo get_phrase('muted_or_inactive_students'); ?></h2>
                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 15px; color: white;">Students who are currently muted or inactive</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <p class="stat-number"><?php echo $total_students; ?></p>
                <p class="stat-label">Total Muted</p>
            </div>
        </div>
    </div>
</div>

<div class="search-box">
    <div style="position: relative;">
        <i class="fa fa-search search-icon"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Search by name, ID, class, or email...">
    </div>
</div>

<div id="studentsContainer">
    <?php if($total_students > 0): ?>
        <?php foreach($students as $student): ?>
        <div class="student-card" data-search="<?php echo strtolower($student['name'] . ' ' . $student['student_code'] . ' ' . ($student['full_class_name'] ?? '') . ' ' . ($student['email'] ?? '')); ?>">
            <div class="row">
                <div class="col-md-7">
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
                                <?php if(!empty($student['full_class_name'])): ?>
                                <span class="meta-item">
                                    <i class="fa fa-graduation-cap"></i>
                                    <span><?php echo $student['full_class_name']; ?></span>
                                </span>
                                <?php endif; ?>
                                <?php if(!empty($student['email'])): ?>
                                <span class="meta-item">
                                    <i class="fa fa-envelope"></i>
                                    <span><?php echo $student['email']; ?></span>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5" style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                    <?php if($student['block_limit'] == 3): ?>
                        <span class="status-badge badge-blocked">
                            <i class="fa fa-lock"></i>
                            Blocked & Muted
                        </span>
                        <a href="javascript:void(0)" onclick="account_unblock(<?php echo $student['student_id']; ?>)" class="action-btn btn-unblock">
                            <i class="fa fa-unlock"></i> Unblock
                        </a>
                        <a href="javascript:void(0)" onclick="account_unmute(<?php echo $student['student_id']; ?>)" class="action-btn btn-unmute">
                            <i class="fa fa-check"></i> Unmute
                        </a>
                    <?php else: ?>
                        <span class="status-badge badge-muted">
                            <i class="fa fa-volume-off"></i>
                            Muted
                        </span>
                        <a href="javascript:void(0)" onclick="account_unmute(<?php echo $student['student_id']; ?>)" class="action-btn btn-unmute">
                            <i class="fa fa-check"></i> Unmute
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo site_url('admin/student_profile/' . $student['student_id']); ?>" class="action-btn btn-view">
                        <i class="fa fa-eye"></i> View
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-inbox" style="font-size: 80px; color: #bdc3c7; margin-bottom: 20px;"></i>
            <h3>No Muted Students</h3>
            <p>Great! There are currently no muted or inactive students.</p>
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

function account_unblock(student_id) {
    showConfirmModal(
        'Confirm Unblock',
        'Are you sure you want to unblock this student?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/student/unblock/"); ?>' + student_id,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message || 'Student unblocked successfully', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message || 'Operation failed', 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Unblock',
        'success'
    );
}

function account_unmute(student_id) {
    // First, show class selection modal
    showClassSelectionModal(student_id);
}

function showClassSelectionModal(student_id) {
    // Get all classes
    $.ajax({
        url: '<?php echo site_url("admin/get_classes"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(classes) {
            if (!classes || classes.length === 0) {
                showAjaxModal_alert('No classes available. Please create classes first.', 'error');
                return;
            }
            
            // Build class selection HTML
            var classOptions = '<div style="margin: 20px 0;">';
            classOptions += '<label style="display: block; margin-bottom: 12px; font-weight: 600; color: #2c3e50; font-size: 15px;">Select Class to Enroll Student:</label>';
            classOptions += '<select id="unmute_class_select" class="form-control" style="width: 100%; padding: 14px 12px; height: 50px; font-size: 15px; border-radius: 8px; border: 2px solid #e8e8e8;">';
            classOptions += '<option value="">-- Select a Class --</option>';
            
            classes.forEach(function(cls) {
                classOptions += '<option value="' + cls.class_id + '">' + cls.name + '</option>';
            });
            
            classOptions += '</select>';
            classOptions += '</div>';
            
            // Show modal with class selection
            showCustomModal(
                'Select Class for Student',
                classOptions,
                function() {
                    var selected_class_id = $('#unmute_class_select').val();
                    
                    if (!selected_class_id) {
                        showAjaxModal_alert('Please select a class', 'error');
                        return false;
                    }
                    
                    // Proceed with unmute
                    performUnmute(student_id, selected_class_id);
                    return true;
                },
                'Unmute & Enroll',
                'success'
            );
        },
        error: function() {
            showAjaxModal_alert('Failed to load classes', 'error');
        }
    });
}

function showCustomModal(title, content, onConfirm, confirmText, confirmType) {
    // Create modal HTML
    var modalHtml = '<div id="custom_modal_overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">';
    modalHtml += '<div style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">';
    modalHtml += '<h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 20px; font-weight: 700;">' + title + '</h3>';
    modalHtml += '<div>' + content + '</div>';
    modalHtml += '<div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">';
    modalHtml += '<button onclick="closeCustomModal()" class="btn btn-default" style="padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 700;">Cancel</button>';
    modalHtml += '<button onclick="confirmCustomModal()" class="btn btn-' + confirmType + '" style="padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 700;">' + confirmText + '</button>';
    modalHtml += '</div>';
    modalHtml += '</div>';
    modalHtml += '</div>';
    
    // Add to body
    $('body').append(modalHtml);
    
    // Store callback
    window.customModalCallback = onConfirm;
}

function closeCustomModal() {
    $('#custom_modal_overlay').remove();
    window.customModalCallback = null;
}

function confirmCustomModal() {
    if (window.customModalCallback) {
        var result = window.customModalCallback();
        if (result !== false) {
            closeCustomModal();
        }
    }
}

function performUnmute(student_id, class_id) {
    showConfirmModal(
        'Confirm Unmute',
        'Are you sure you want to unmute this student and enroll them in the selected class?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/student/unmute/"); ?>' + student_id + '/' + class_id,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message || 'Student unmuted successfully', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message || 'Operation failed', 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Unmute',
        'success'
    );
}
</script>
