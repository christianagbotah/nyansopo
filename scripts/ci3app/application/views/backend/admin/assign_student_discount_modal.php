<!-- MODAL UPDATED VERSION 2.0 - CASHIER FILTER -->
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

<?php echo form_open('admin/assign_student_discount/create', array('id' => 'assign_form_modal')); ?>
    
    <div class="modern-form-group">
        <label class="modern-label"><i class="fa fa-users"></i><?php echo get_phrase('search_students'); ?></label>
        <div style="position: relative;">
            <input type="text" id="student_search_modal" placeholder="<?php echo get_phrase('type_student_name_or_code'); ?>" class="modern-input" autocomplete="off">
            <div id="student_suggestions_modal" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 400px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
        </div>
        <div id="selected_students_display_modal" style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 50px; padding: 12px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e2e8f0;"></div>
    </div>

    <div class="modern-form-group">
        <label class="modern-label"><i class="fa fa-tag"></i><?php echo get_phrase('select_discount_profiles'); ?></label>
        <div style="position: relative;">
            <input type="text" id="profile_search_modal" placeholder="<?php echo get_phrase('type_profile_name'); ?>" class="modern-input" autocomplete="off">
            <div id="profile_suggestions_modal" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
        </div>
        <div id="selected_profiles_display_modal" style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 50px; padding: 12px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e2e8f0;"></div>
        <small style="color: #718096; margin-top: 6px; display: block;">
            <i class="fa fa-info-circle"></i> <?php echo get_phrase('discount_will_be_assigned_for_running_year_term'); ?>
        </small>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="modern-btn">
            <i class="fa fa-check"></i><?php echo get_phrase('assign_discount'); ?>
        </button>
    </div>
<?php echo form_close(); ?>

<script>
(function() {
    // Cleanup old handlers from previous modal instances
    $(document).off('click.assignDiscountSuggestion');
    $(document).off('click.assignDiscountProfile');
    $(document).off('click.assignDiscountModal');
    
    let allStudentsModal = [];
    let selectedStudentsModal = [];
    // Filter profiles based on user role - cashiers only see daily_fees profiles
    let allProfilesModal = <?php 
        $user_level = $this->session->userdata('user_type'); // Correct session key is 'user_type'
        $is_cashier = ($user_level == 4);
        
        if($is_cashier) {
            // Filter to only daily_fees category for cashiers
            $filtered_profiles = array_filter($profiles, function($profile) {
                return isset($profile['discount_category']) && $profile['discount_category'] === 'daily_fees';
            });
            echo json_encode(array_values($filtered_profiles));
        } else {
            echo json_encode($profiles);
        }
    ?>;
    let selectedProfilesModal = [];
    let studentSearchTimeout = null;
    let profileSearchTimeout = null;
    let studentAssignedProfiles = {};

    function initModalData() {
        selectedStudentsModal = [];
        selectedProfilesModal = [];
        studentAssignedProfiles = {};
        $('#student_search_modal').val('');
        $('#profile_search_modal').val('');
        $('#student_suggestions_modal').hide().empty();
        $('#profile_suggestions_modal').hide().empty();
        updateSelectedDisplayModal();
        updateProfileDisplayModal();
        
        if(allStudentsModal.length === 0) {
            $.ajax({
                url: '<?php echo site_url('admin/get_students_for_discount'); ?>',
                type: 'GET',
                dataType: 'json',
                async: false,
                success: function(response) {
                    if(response.status === 'success') {
                        allStudentsModal = response.data;
                    }
                }
            });
        }
    }

    function attachModalEvents() {
        $('#student_search_modal').off('input').on('input', function() {
            const searchTerm = $(this).val().trim();
            clearTimeout(studentSearchTimeout);
            
            if(searchTerm.length < 2) {
                $('#student_suggestions_modal').hide().empty();
                return;
            }
            
            studentSearchTimeout = setTimeout(() => {
                const filtered = allStudentsModal.filter(s => 
                    !selectedStudentsModal.includes(String(s.student_id)) && 
                    (s.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                     s.student_code.toLowerCase().includes(searchTerm.toLowerCase()))
                );
                displaySuggestionsModal(filtered);
            }, 300);
        });

        $('#profile_search_modal').off('click focus input');
        $('#profile_search_modal').on('click focus', function() {
            const filtered = allProfilesModal.filter(p => !selectedProfilesModal.includes(String(p.profile_id)));
            displayProfileSuggestionsModal(filtered);
        });

        $('#profile_search_modal').on('input', function() {
            const searchTerm = $(this).val().trim();
            clearTimeout(profileSearchTimeout);
            
            if(searchTerm.length < 1) {
                const filtered = allProfilesModal.filter(p => !selectedProfilesModal.includes(String(p.profile_id)));
                displayProfileSuggestionsModal(filtered);
                return;
            }
            
            profileSearchTimeout = setTimeout(() => {
                const filtered = allProfilesModal.filter(p => 
                    !selectedProfilesModal.includes(String(p.profile_id)) && 
                    p.profile_name.toLowerCase().includes(searchTerm.toLowerCase())
                );
                displayProfileSuggestionsModal(filtered);
            }, 300);
        });
    }

    // Initialize immediately when script loads
    $(document).ready(function() {
        if($('#assign_form_modal').length) {
            initModalData();
            attachModalEvents();
        }
    });

    $(document).on('click.assignDiscountSuggestion', '.suggestion-item-modal', function() {
        const studentId = String($(this).data('id'));
        if(!selectedStudentsModal.includes(studentId)) {
            selectedStudentsModal.push(studentId);
            
            // Check for existing assignments
            $.ajax({
                url: '<?php echo site_url('admin/get_student_assigned_profiles'); ?>',
                type: 'GET',
                data: {student_id: studentId},
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success' && response.data.length > 0) {
                        studentAssignedProfiles[studentId] = response.data.map(p => String(p.profile_id));
                        const profileNames = response.data.map(p => p.profile_name).join(', ');
                        showAjaxModal_alert(`Note: This student already has these profiles assigned: ${profileNames}`, 'warning');
                    }
                    updateSelectedDisplayModal();
                    updateProfileDisplayModal();
                }
            });
            
            $('#student_search_modal').val('');
            $('#student_suggestions_modal').hide();
        }
    });

    window.removeStudentModal = function(studentId) {
        selectedStudentsModal = selectedStudentsModal.filter(id => id !== studentId);
        updateSelectedDisplayModal();
    };

    function updateSelectedDisplayModal() {
        if(selectedStudentsModal.length === 0) {
            $('#selected_students_display_modal').html('<div style="color: #94a3b8; text-align: center; width: 100%; padding: 6px; font-size: 13px;"><i class="fa fa-info-circle"></i> No students selected yet</div>');
            return;
        }
        
        let html = '';
        selectedStudentsModal.forEach(id => {
            const student = allStudentsModal.find(s => String(s.student_id) === String(id));
            if(student) {
                html += `
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.25);">
                        <div>
                            <div style="font-size: 12px;">${student.name}</div>
                            <div style="font-size: 10px; opacity: 0.9;">${student.student_code} • ${student.class_name}</div>
                        </div>
                        <span onclick="removeStudentModal('${id}')" style="cursor: pointer; font-weight: bold; opacity: 0.8; font-size: 16px; margin-left: 2px; line-height: 1;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</span>
                    </div>
                `;
            }
        });
        $('#selected_students_display_modal').html(html);
    }

    function displaySuggestionsModal(data) {
        if(data.length === 0) {
            $('#student_suggestions_modal').html('<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 13px;">No students found</div>').show();
            return;
        }
        
        let html = '';
        data.forEach(student => {
            html += `
                <div class="suggestion-item-modal" data-id="${student.student_id}" data-name="${student.name}" data-code="${student.student_code}" data-class="${student.class_name}" style="padding: 8px 12px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.suggestion-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.suggestion-meta').style.color='#718096';">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 13px; margin-bottom: 2px;">
                                <i class="fa fa-user" style="margin-right: 5px; font-size: 11px;"></i>${student.name}
                            </div>
                            <div class="suggestion-meta" style="font-size: 11px; color: #718096;">
                                <span style="margin-right: 10px;"><i class="fa fa-id-card" style="margin-right: 3px;"></i>${student.student_code}</span>
                                <span><i class="fa fa-school" style="margin-right: 3px;"></i>${student.class_name}</span>
                            </div>
                        </div>
                        <i class="fa fa-plus-circle" style="font-size: 16px; opacity: 0.5;"></i>
                    </div>
                </div>
            `;
        });
        $('#student_suggestions_modal').html(html).slideDown(200);
    }

    function displayProfileSuggestionsModal(data) {
    let assignedProfileIds = [];
    selectedStudentsModal.forEach(studentId => {
        if(studentAssignedProfiles[studentId]) {
            assignedProfileIds = assignedProfileIds.concat(studentAssignedProfiles[studentId]);
        }
    });
    
    const filteredData = data.filter(p => !assignedProfileIds.includes(String(p.profile_id)));
    
    if(filteredData.length === 0) {
        $('#profile_suggestions_modal').html('<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 13px;">No available profiles (already assigned)</div>').show();
        return;
    }
    
    let html = '';
    filteredData.forEach(profile => {
        const discountValue = profile.discount_value ? parseFloat(profile.discount_value).toFixed(2) : '0.00';
        const discountMethod = profile.discount_method || 'percentage';
        const valueDisplay = discountMethod === 'percentage' ? discountValue + '%' : 'GHS ' + discountValue;
        const categoryBadge = profile.discount_category === 'invoice' ? '📄 Invoice' : '📅 Daily Fees';
        
        let typeDisplay = 'N/A';
        if(profile.discount_category === 'invoice') {
            if(profile.bill_item_ids === '*') {
                typeDisplay = 'All Invoice Items';
            } else if(profile.bill_item_ids) {
                const billItemsMap = <?php echo json_encode($bill_items_map); ?>;
                typeDisplay = profile.bill_item_ids.split(',').map(id => {
                    const title = billItemsMap[id] || 'Unknown Item';
                    return title.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
                }).join(', ');
            }
        } else if(profile.discount_category === 'daily_fees' && profile.discount_type) {
            typeDisplay = profile.discount_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
        
        html += `
            <div class="profile-item-modal" data-id="${profile.profile_id}" data-name="${profile.profile_name}" style="padding: 8px 12px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.profile-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.profile-meta').style.color='#718096';">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 13px; margin-bottom: 2px;">
                            <i class="fa fa-tag" style="margin-right: 5px; font-size: 11px;"></i>${profile.profile_name}
                        </div>
                        <div class="profile-meta" style="font-size: 11px; color: #718096;">
                            <span style="margin-right: 8px;"><i class="fa fa-${discountMethod === 'percentage' ? 'percent' : 'dollar'}" style="margin-right: 3px;"></i>${valueDisplay}</span>
                            <span style="margin-right: 8px;">${categoryBadge}</span>
                            <span>${typeDisplay}</span>
                        </div>
                    </div>
                    <i class="fa fa-plus-circle" style="font-size: 16px; opacity: 0.5;"></i>
                </div>
            </div>
        `;
    });
    $('#profile_suggestions_modal').html(html).slideDown(200);
}

    $(document).on('click.assignDiscountProfile', '.profile-item-modal', function() {
        const profileId = String($(this).data('id'));
        if(!selectedProfilesModal.includes(profileId)) {
            selectedProfilesModal.push(profileId);
            updateProfileDisplayModal();
            $('#profile_search_modal').val('');
            $('#profile_suggestions_modal').hide();
        }
    });

    window.removeProfileModal = function(profileId) {
        selectedProfilesModal = selectedProfilesModal.filter(id => id !== profileId);
        updateProfileDisplayModal();
    };

    function updateProfileDisplayModal() {
    if(selectedProfilesModal.length === 0) {
        $('#selected_profiles_display_modal').html('<div style="color: #94a3b8; text-align: center; width: 100%; padding: 6px; font-size: 13px;"><i class="fa fa-info-circle"></i> No profiles selected yet</div>');
        return;
    }
    
    let html = '';
    selectedProfilesModal.forEach(id => {
        const profile = allProfilesModal.find(p => String(p.profile_id) === String(id));
        if(profile) {
            const discountValue = profile.discount_value ? parseFloat(profile.discount_value).toFixed(2) : '0.00';
            const discountMethod = profile.discount_method || 'percentage';
            const valueDisplay = discountMethod === 'percentage' ? discountValue + '%' : 'GHS ' + discountValue;
            const categoryBadge = profile.discount_category === 'invoice' ? '📄' : '📅';
            
            let typeDisplay = 'N/A';
            if(profile.discount_category === 'invoice') {
                if(profile.bill_item_ids === '*') {
                    typeDisplay = 'All Invoice Items';
                } else if(profile.bill_item_ids) {
                    const billItemsMap = <?php echo json_encode($bill_items_map); ?>;
                    typeDisplay = profile.bill_item_ids.split(',').map(id => {
                        const title = billItemsMap[id] || 'Unknown Item';
                        return title.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
                    }).join(', ');
                }
            } else if(profile.discount_category === 'daily_fees' && profile.discount_type) {
                typeDisplay = profile.discount_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
            
            html += `
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 6px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; box-shadow: 0 2px 4px rgba(245, 87, 108, 0.25);">
                    <div>
                        <div style="font-size: 12px;">${categoryBadge} ${profile.profile_name}</div>
                        <div style="font-size: 10px; opacity: 0.9;">${typeDisplay} • ${valueDisplay}</div>
                    </div>
                    <span onclick="removeProfileModal('${id}')" style="cursor: pointer; font-weight: bold; opacity: 0.8; font-size: 16px; margin-left: 2px; line-height: 1;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</span>
                </div>
            `;
        }
    });
    $('#selected_profiles_display_modal').html(html);
}

    $(document).on('click.assignDiscountModal', function(e) {
        if($('#modal_ajax').hasClass('in') && $('#assign_form_modal').length) {
            if(!$(e.target).closest('#student_search_modal, #student_suggestions_modal').length) {
                $('#student_suggestions_modal').hide();
            }
            if(!$(e.target).closest('#profile_search_modal, #profile_suggestions_modal').length) {
                $('#profile_suggestions_modal').hide();
            }
        }
    });

    $('#assign_form_modal').submit(function(e) {
    e.preventDefault();
    
    if(selectedStudentsModal.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_students"); ?>', 'error');
        return;
    }
    
    if(selectedProfilesModal.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_profiles"); ?>', 'error');
        return;
    }
    
    $('.close').click();
    showAjaxModal_alert('<?php echo get_phrase("assigning_discount"); ?>...', 'loading');
    
    let postData = $(this).serialize();
    selectedStudentsModal.forEach(id => {
        postData += '&student_id[]=' + encodeURIComponent(id);
    });
    selectedProfilesModal.forEach(id => {
        postData += '&profile_id[]=' + encodeURIComponent(id);
    });
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: postData,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false, true);
            // Keep alert open and reload page after delay
            setTimeout(function() {
                if(typeof loadData === 'function') {
                    loadData();
                } else {
                    location.reload();
                }
            }, 2000);
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
                            showAjaxModal_alert(res.message, 'success', false);
                            // Close modal and refresh data
                            $('#modal_ajax').modal('hide');
                            if(typeof loadData === 'function') {
                                setTimeout(() => loadData(), 500);
                            } else {
                                setTimeout(() => location.reload(), 500);
                            }
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
})();
</script>
