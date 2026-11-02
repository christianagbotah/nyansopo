<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.modern-form-group { margin-bottom: 28px; }
.modern-label { display: block; font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 12px; letter-spacing: 0.3px; }
.modern-label i { color: #667eea; margin-right: 8px; }
.modern-input { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.2s; background: #ffffff; color: #1a202c; font-weight: 500; }
.modern-input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); background: #f7fafc; }
.modern-input::placeholder { color: #a0aec0; font-weight: 400; }
.modern-btn { padding: 16px 32px; border-radius: 10px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.modern-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.modern-btn i { margin-right: 0; }
</style>

<?php echo form_open('admin/bulk_assign_by_class/assign', array('id' => 'bulkAssignFormModal')); ?>
    
    <div class="modern-form-group">
        <label class="modern-label"><i class="fa fa-school"></i><?php echo get_phrase('select_classes'); ?></label>
        <div style="position: relative;">
            <input type="text" id="class_search_modal" placeholder="<?php echo get_phrase('type_class_name'); ?>" class="modern-input" autocomplete="off">
            <div id="class_suggestions_modal" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
        </div>
        <div id="selected_classes_display_modal" style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 50px; padding: 12px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e2e8f0;"></div>
    </div>
    
    <div class="modern-form-group">
        <label class="modern-label"><i class="fa fa-tag"></i><?php echo get_phrase('select_discount_profiles'); ?></label>
        <div style="position: relative;">
            <input type="text" id="profile_search_modal" placeholder="<?php echo get_phrase('type_profile_name'); ?>" class="modern-input" autocomplete="off">
            <div id="profile_suggestions_modal" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
        </div>
        <div id="selected_profiles_display_modal" style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 50px; padding: 12px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e2e8f0;"></div>
        <small style="color: #718096; margin-top: 6px; display: block;">
            <i class="fa fa-info-circle"></i> <?php echo get_phrase('all_students_in_selected_classes_will_be_assigned'); ?>
        </small>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="modern-btn">
            <i class="fa fa-check"></i><?php echo get_phrase('assign_discount'); ?>
        </button>
    </div>
<?php echo form_close(); ?>

<script>
let allClassesModal = <?php echo json_encode(array_map(function($c) { 
    return array(
        'class_id' => $c['class_id'], 
        'name' => $c['name'], 
        'name_numeric' => $c['name_numeric'],
        'section_name' => $c['section_name'],
        'student_count' => $c['student_count']
    ); 
}, $classes)); ?>;
let selectedClassesModal = [];
let allProfilesModal = <?php echo json_encode($profiles); ?>;
let selectedProfilesModal = [];
let searchTimeoutModal = null;

function initBulkModalData() {
    selectedClassesModal = [];
    selectedProfilesModal = [];
    $('#class_search_modal').val('');
    $('#profile_search_modal').val('');
    $('#class_suggestions_modal').hide();
    $('#profile_suggestions_modal').hide();
    updateClassDisplayModal();
    updateProfileDisplayModal();
}

$(document).ready(function() {
    initBulkModalData();
});

$('#modal_ajax').on('shown.bs.modal', function() {
    setTimeout(function() {
        if($('#bulkAssignFormModal').length) {
            initBulkModalData();
    
            $('#class_search_modal').off('click focus input');
            $('#class_search_modal').on('click focus', function() {
                const filtered = allClassesModal.filter(c => !selectedClassesModal.includes(String(c.class_id)));
                displayClassSuggestionsModal(filtered);
            });

            $('#class_search_modal').on('input', function() {
                const searchTerm = $(this).val().trim();
                clearTimeout(searchTimeoutModal);
                
                if(searchTerm.length < 1) {
                    const filtered = allClassesModal.filter(c => !selectedClassesModal.includes(String(c.class_id)));
                    displayClassSuggestionsModal(filtered);
                    return;
                }
                
                searchTimeoutModal = setTimeout(() => {
                    const filtered = allClassesModal.filter(c => 
                        !selectedClassesModal.includes(String(c.class_id)) && 
                        (c.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                         c.name_numeric.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         c.section_name.toLowerCase().includes(searchTerm.toLowerCase()))
                    );
                    displayClassSuggestionsModal(filtered);
                }, 300);
            });

            $('#profile_search_modal').off('click focus input');
            $('#profile_search_modal').on('click focus', function() {
                const filtered = allProfilesModal.filter(p => !selectedProfilesModal.includes(String(p.profile_id)));
                displayProfileSuggestionsModal(filtered);
            });

            $('#profile_search_modal').on('input', function() {
                const searchTerm = $(this).val().trim();
                clearTimeout(searchTimeoutModal);
                
                if(searchTerm.length < 1) {
                    const filtered = allProfilesModal.filter(p => !selectedProfilesModal.includes(String(p.profile_id)));
                    displayProfileSuggestionsModal(filtered);
                    return;
                }
                
                searchTimeoutModal = setTimeout(() => {
                    const filtered = allProfilesModal.filter(p => 
                        !selectedProfilesModal.includes(String(p.profile_id)) && 
                        p.profile_name.toLowerCase().includes(searchTerm.toLowerCase())
                    );
                    displayProfileSuggestionsModal(filtered);
                }, 300);
            });
        }
    }, 100);
});

function displayClassSuggestionsModal(data) {
    if(data.length === 0) {
        $('#class_suggestions_modal').html('<div style="padding: 16px; text-align: center; color: #94a3b8;">No classes found</div>').show();
        return;
    }
    
    let html = '';
    data.forEach(cls => {
        const fullName = cls.name + ' ' + cls.name_numeric + ' ' + cls.section_name;
        const studentInfo = cls.student_count > 0 ? cls.student_count + ' student' + (cls.student_count !== 1 ? 's' : '') : 'No students';
        html += `
            <div class="class-item-modal" data-id="${cls.class_id}" data-name="${fullName}" data-count="${cls.student_count}" style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.class-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.class-meta').style.color='#718096';">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                            <i class="fa fa-school" style="margin-right: 6px; font-size: 12px;"></i>${fullName}
                        </div>
                        <div class="class-meta" style="font-size: 12px; color: #718096;">
                            <i class="fa fa-users" style="margin-right: 4px;"></i>${studentInfo}
                        </div>
                    </div>
                    <i class="fa fa-plus-circle" style="font-size: 20px; opacity: 0.5;"></i>
                </div>
            </div>
        `;
    });
    $('#class_suggestions_modal').html(html).slideDown(200);
}

$(document).off('click', '.class-item-modal').on('click', '.class-item-modal', function() {
    const classId = String($(this).data('id'));
    if(!selectedClassesModal.includes(classId)) {
        selectedClassesModal.push(classId);
        updateClassDisplayModal();
        $('#class_search_modal').val('');
        $('#class_suggestions_modal').hide();
    }
});

function removeClassModal(classId) {
    selectedClassesModal = selectedClassesModal.filter(id => id !== classId);
    updateClassDisplayModal();
}

function updateClassDisplayModal() {
    if(selectedClassesModal.length === 0) {
        $('#selected_classes_display_modal').html('<div style="color: #94a3b8; text-align: center; width: 100%; padding: 8px;"><i class="fa fa-info-circle"></i> No classes selected yet</div>');
        return;
    }
    
    let html = '';
    selectedClassesModal.forEach(id => {
        const cls = allClassesModal.find(c => String(c.class_id) === String(id));
        if(cls) {
            const fullName = cls.name + ' ' + cls.name_numeric + ' ' + cls.section_name;
            const studentInfo = cls.student_count > 0 ? cls.student_count + ' student' + (cls.student_count !== 1 ? 's' : '') : 'No students';
            html += `
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);">
                    <div>
                        <div>${fullName}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${studentInfo}</div>
                    </div>
                    <span onclick="removeClassModal('${id}')" style="cursor: pointer; font-weight: bold; opacity: 0.8; font-size: 18px; margin-left: 4px;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</span>
                </div>
            `;
        }
    });
    $('#selected_classes_display_modal').html(html);
}

function displayProfileSuggestionsModal(data) {
    if(data.length === 0) {
        $('#profile_suggestions_modal').html('<div style="padding: 16px; text-align: center; color: #94a3b8;">No profiles found</div>').show();
        return;
    }
    
    let html = '';
    data.forEach(profile => {
        const discountType = profile.discount_type.charAt(0).toUpperCase() + profile.discount_type.slice(1);
        const discountValues = profile.discount_values ? profile.discount_values + '%' : 'N/A';
        html += `
            <div class="profile-item-modal" data-id="${profile.profile_id}" data-name="${profile.profile_name}" style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.profile-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.profile-meta').style.color='#718096';">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                            <i class="fa fa-tag" style="margin-right: 6px; font-size: 12px;"></i>${profile.profile_name}
                        </div>
                        <div class="profile-meta" style="font-size: 12px; color: #718096;">
                            <span><i class="fa fa-percent" style="margin-right: 4px;"></i>${discountType} • ${discountValues}</span>
                        </div>
                    </div>
                    <i class="fa fa-plus-circle" style="font-size: 20px; opacity: 0.5;"></i>
                </div>
            </div>
        `;
    });
    $('#profile_suggestions_modal').html(html).slideDown(200);
}

$(document).off('click', '.profile-item-modal').on('click', '.profile-item-modal', function() {
    const profileId = String($(this).data('id'));
    if(!selectedProfilesModal.includes(profileId)) {
        selectedProfilesModal.push(profileId);
        updateProfileDisplayModal();
        $('#profile_search_modal').val('');
        $('#profile_suggestions_modal').hide();
    }
});

function removeProfileModal(profileId) {
    selectedProfilesModal = selectedProfilesModal.filter(id => id !== profileId);
    updateProfileDisplayModal();
}

function updateProfileDisplayModal() {
    if(selectedProfilesModal.length === 0) {
        $('#selected_profiles_display_modal').html('<div style="color: #94a3b8; text-align: center; width: 100%; padding: 8px;"><i class="fa fa-info-circle"></i> No profiles selected yet</div>');
        return;
    }
    
    let html = '';
    selectedProfilesModal.forEach(id => {
        const profile = allProfilesModal.find(p => String(p.profile_id) === String(id));
        if(profile) {
            const discountType = profile.discount_type.charAt(0).toUpperCase() + profile.discount_type.slice(1);
            const discountValues = profile.discount_values ? profile.discount_values + '%' : 'N/A';
            html += `
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 10px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(245, 87, 108, 0.3);">
                    <div>
                        <div>${profile.profile_name}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${discountType} • ${discountValues}</div>
                    </div>
                    <span onclick="removeProfileModal('${id}')" style="cursor: pointer; font-weight: bold; opacity: 0.8; font-size: 18px; margin-left: 4px;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</span>
                </div>
            `;
        }
    });
    $('#selected_profiles_display_modal').html(html);
}

$(document).off('click.modalDropdown').on('click.modalDropdown', function(e) {
    if(!$(e.target).closest('#class_search_modal, #class_suggestions_modal').length) {
        $('#class_suggestions_modal').hide();
    }
    if(!$(e.target).closest('#profile_search_modal, #profile_suggestions_modal').length) {
        $('#profile_suggestions_modal').hide();
    }
});

$('#bulkAssignFormModal').submit(function(e) {
    e.preventDefault();
    
    if(selectedClassesModal.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_classes"); ?>', 'error');
        return;
    }
    
    if(selectedProfilesModal.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_profiles"); ?>', 'error');
        return;
    }
    
    $('.close').click();
    showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
    
    let postData = '';
    selectedClassesModal.forEach(id => {
        postData += 'class_names[]=' + encodeURIComponent(id) + '&';
    });
    selectedProfilesModal.forEach(id => {
        postData += 'profile_ids[]=' + encodeURIComponent(id) + '&';
    });
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: postData,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => loadData(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase("an_error_occurred"); ?>', 'error');
    });
});
</script>
