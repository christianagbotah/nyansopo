<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.modern-container { max-width: 900px; margin: 0 auto; padding: 24px; }
.modern-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25); }
.modern-header h2 { margin: 0 0 8px 0; font-size: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; color: white; }
.modern-header p { margin: 0; opacity: 0.95; font-size: 15px; }
.modern-card { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.modern-form-group { margin-bottom: 32px; }
.modern-label { display: block; font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 12px; letter-spacing: 0.3px; }
.modern-label i { color: #667eea; margin-right: 8px; }
.modern-input, .modern-select { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.2s; background: #ffffff; color: #1a202c; font-weight: 500; }
.modern-input:focus, .modern-select:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); background: #f7fafc; }
.modern-actions { display: flex; gap: 12px; margin-top: 40px; }
.modern-btn { padding: 16px 32px; border-radius: 10px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.modern-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.modern-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.modern-btn-secondary { background: white; color: #374151; border: 2px solid #e5e7eb; }
.modern-btn-secondary:hover { border-color: #667eea; color: #667eea; }
</style>

<div class="modern-container">
    <div class="modern-header">
        <h2><i class="fa fa-user-plus"></i><?php echo get_phrase('assign_discount_profiles'); ?></h2>
        <p><?php echo get_phrase('search_students_and_assign_discount_profiles'); ?></p>
    </div>

    <div class="modern-card">
        <?php echo form_open('admin/assign_student_discount/create', array('id' => 'assign_form')); ?>
            
            <div class="modern-form-group">
                <label class="modern-label"><i class="fa fa-users"></i><?php echo get_phrase('search_students'); ?></label>
                <div style="position: relative;">
                    <input type="text" id="student_search" placeholder="<?php echo get_phrase('type_student_name_or_code'); ?>" class="modern-input" autocomplete="off">
                    <div id="student_suggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 400px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
                </div>
                <div id="selected_students_display" style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 50px; padding: 12px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e2e8f0;"></div>
            </div>

            <div class="modern-form-group">
                <label class="modern-label"><i class="fa fa-tag"></i><?php echo get_phrase('select_discount_profiles'); ?></label>
                <div style="position: relative;">
                    <input type="text" id="profile_search" placeholder="<?php echo get_phrase('type_profile_name'); ?>" class="modern-input" autocomplete="off">
                    <div id="profile_suggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
                </div>
                <div id="selected_profiles_display" style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 50px; padding: 12px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e2e8f0;"></div>
                <small style="color: #718096; margin-top: 8px; display: block;">
                    <i class="fa fa-info-circle"></i> <?php echo get_phrase('discount_will_be_assigned_for_running_year_term'); ?>
                </small>
            </div>

            <div class="modern-actions">
                <button type="submit" class="modern-btn modern-btn-primary">
                    <i class="fa fa-check"></i><?php echo get_phrase('assign_discount'); ?>
                </button>
                <a href="<?php echo site_url('admin/manage_discount_assignments'); ?>" class="modern-btn modern-btn-secondary">
                    <i class="fa fa-arrow-left"></i><?php echo get_phrase('back'); ?>
                </a>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>

<script>
let allStudents = [];
let selectedStudents = [];
let allProfiles = <?php echo json_encode($profiles); ?>;
let selectedProfiles = [];
let searchTimeout = null;

$(document).ready(function() {
    // Load all students on page load
    $.ajax({
        url: '<?php echo site_url('admin/get_students_for_discount'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                allStudents = response.data;
                console.log('Loaded ' + allStudents.length + ' students');
            }
        }
    });
    
    updateProfileDisplay();
});

$('#student_search').on('input', function() {
    const searchTerm = $(this).val().trim();
    
    clearTimeout(searchTimeout);
    
    if(searchTerm.length < 2) {
        $('#student_suggestions').hide().empty();
        return;
    }
    
    searchTimeout = setTimeout(() => {
        const filtered = allStudents.filter(s => 
            !selectedStudents.includes(String(s.student_id)) && 
            (s.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
             s.student_code.toLowerCase().includes(searchTerm.toLowerCase()))
        );
        displaySuggestions(filtered);
    }, 300);
});

function displaySuggestions(data) {
    if(data.length === 0) {
        $('#student_suggestions').html('<div style="padding: 16px; text-align: center; color: #94a3b8;">No students found</div>').show();
        return;
    }
    
    let html = '';
    data.forEach(student => {
        html += `
            <div class="suggestion-item" data-id="${student.student_id}" data-name="${student.name}" data-code="${student.student_code}" data-class="${student.class_name}" style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.suggestion-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.suggestion-meta').style.color='#718096';">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                            <i class="fa fa-user" style="margin-right: 6px; font-size: 12px;"></i>${student.name}
                        </div>
                        <div class="suggestion-meta" style="font-size: 12px; color: #718096;">
                            <span style="margin-right: 12px;"><i class="fa fa-id-card" style="margin-right: 4px;"></i>${student.student_code}</span>
                            <span><i class="fa fa-school" style="margin-right: 4px;"></i>${student.class_name}</span>
                        </div>
                    </div>
                    <i class="fa fa-plus-circle" style="font-size: 20px; opacity: 0.5;"></i>
                </div>
            </div>
        `;
    });
    $('#student_suggestions').html(html).slideDown(200);
}

$(document).on('click', '.suggestion-item', function() {
    const studentId = String($(this).data('id'));
    
    if(!selectedStudents.includes(studentId)) {
        selectedStudents.push(studentId);
        updateSelectedDisplay();
        $('#student_search').val('');
        $('#student_suggestions').hide();
    }
});

function removeStudent(studentId) {
    selectedStudents = selectedStudents.filter(id => id !== studentId);
    updateSelectedDisplay();
}

function updateSelectedDisplay() {
    if(selectedStudents.length === 0) {
        $('#selected_students_display').html('<div style="color: #94a3b8; text-align: center; width: 100%; padding: 8px;"><i class="fa fa-info-circle"></i> No students selected yet</div>');
        return;
    }
    
    let html = '';
    selectedStudents.forEach(id => {
        const student = allStudents.find(s => String(s.student_id) === String(id));
        if(student) {
            html += `
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);">
                    <div>
                        <div>${student.name}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${student.student_code} • ${student.class_name}</div>
                    </div>
                    <span onclick="removeStudent('${id}')" style="cursor: pointer; font-weight: bold; opacity: 0.8; font-size: 18px; margin-left: 4px;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</span>
                </div>
            `;
        }
    });
    $('#selected_students_display').html(html);
}

$(document).on('click', function(e) {
    if(!$(e.target).closest('#student_search, #student_suggestions').length) {
        $('#student_suggestions').hide();
    }
    if(!$(e.target).closest('#profile_search, #profile_suggestions').length) {
        $('#profile_suggestions').hide();
    }
});

// Profile search - show all on click/focus
$('#profile_search').on('click focus', function() {
    const filtered = allProfiles.filter(p => !selectedProfiles.includes(String(p.profile_id)));
    displayProfileSuggestions(filtered);
});

$('#profile_search').on('input', function() {
    const searchTerm = $(this).val().trim();
    
    clearTimeout(searchTimeout);
    
    if(searchTerm.length < 1) {
        const filtered = allProfiles.filter(p => !selectedProfiles.includes(String(p.profile_id)));
        displayProfileSuggestions(filtered);
        return;
    }
    
    searchTimeout = setTimeout(() => {
        const filtered = allProfiles.filter(p => 
            !selectedProfiles.includes(String(p.profile_id)) && 
            p.profile_name.toLowerCase().includes(searchTerm.toLowerCase())
        );
        displayProfileSuggestions(filtered);
    }, 300);
});

function displayProfileSuggestions(data) {
    if(data.length === 0) {
        $('#profile_suggestions').html('<div style="padding: 16px; text-align: center; color: #94a3b8;">No profiles found</div>').show();
        return;
    }
    
    let html = '';
    data.forEach(profile => {
        html += `
            <div class="profile-item" data-id="${profile.profile_id}" data-name="${profile.profile_name}" data-type="${profile.discount_type}" style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.profile-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.profile-meta').style.color='#718096';">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                            <i class="fa fa-tag" style="margin-right: 6px; font-size: 12px;"></i>${profile.profile_name}
                        </div>
                        <div class="profile-meta" style="font-size: 12px; color: #718096;">
                            <span><i class="fa fa-info-circle" style="margin-right: 4px;"></i>${profile.discount_type.charAt(0).toUpperCase() + profile.discount_type.slice(1)}</span>
                        </div>
                    </div>
                    <i class="fa fa-plus-circle" style="font-size: 20px; opacity: 0.5;"></i>
                </div>
            </div>
        `;
    });
    $('#profile_suggestions').html(html).slideDown(200);
}

$(document).on('click', '.profile-item', function() {
    const profileId = String($(this).data('id'));
    
    if(!selectedProfiles.includes(profileId)) {
        selectedProfiles.push(profileId);
        updateProfileDisplay();
        $('#profile_search').val('');
        $('#profile_suggestions').hide();
    }
});

function removeProfile(profileId) {
    selectedProfiles = selectedProfiles.filter(id => id !== profileId);
    updateProfileDisplay();
}

function updateProfileDisplay() {
    if(selectedProfiles.length === 0) {
        $('#selected_profiles_display').html('<div style="color: #94a3b8; text-align: center; width: 100%; padding: 8px;"><i class="fa fa-info-circle"></i> No profiles selected yet</div>');
        return;
    }
    
    let html = '';
    selectedProfiles.forEach(id => {
        const profile = allProfiles.find(p => String(p.profile_id) === String(id));
        if(profile) {
            html += `
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 10px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(245, 87, 108, 0.3);">
                    <div>
                        <div>${profile.profile_name}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${profile.discount_type.charAt(0).toUpperCase() + profile.discount_type.slice(1)}</div>
                    </div>
                    <span onclick="removeProfile('${id}')" style="cursor: pointer; font-weight: bold; opacity: 0.8; font-size: 18px; margin-left: 4px;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</span>
                </div>
            `;
        }
    });
    $('#selected_profiles_display').html(html);
}

// Initialize displays
updateSelectedDisplay();
updateProfileDisplay();

$('#assign_form').submit(function(e) {
    e.preventDefault();
    
    if(selectedStudents.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_students"); ?>', 'error');
        return;
    }
    
    if(selectedProfiles.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_profiles"); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase("assigning_discount"); ?>...', 'loading');
    
    let postData = $(this).serialize();
    selectedStudents.forEach(id => {
        postData += '&student_id[]=' + encodeURIComponent(id);
    });
    selectedProfiles.forEach(id => {
        postData += '&profile_id[]=' + encodeURIComponent(id);
    });
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: postData,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.href = '<?php echo site_url('admin/manage_discount_assignments'); ?>', 2000);
        } else if(response.status === 'confirm') {
            let duplicateList = response.duplicates.map(d => `${d.student} - ${d.profile}`).join('<br>');
            showConfirmModal(
                'Duplicate Assignments Found',
                `The following assignments already exist:<br><br>${duplicateList}<br><br>Do you want to update them?`,
                function() {
                    showAjaxModal_alert('<?php echo get_phrase("assigning_discount"); ?>...', 'loading');
                    $.ajax({
                        url: '<?php echo site_url('admin/assign_student_discount/create'); ?>',
                        type: 'POST',
                        data: postData + '&confirm=1',
                        dataType: 'json'
                    }).done(function(res) {
                        if(res.status === 'success') {
                            showAjaxModal_alert(res.message, 'success');
                            setTimeout(() => location.href = '<?php echo site_url('admin/manage_discount_assignments'); ?>', 2000);
                        } else {
                            showAjaxModal_alert(res.message, 'error');
                        }
                    });
                },
                'Update',
                'warning'
            );
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase("an_error_occurred"); ?>', 'error');
    });
});
</script>
