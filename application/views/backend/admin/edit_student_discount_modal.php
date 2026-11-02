<?php
$student = $this->db->select('s.student_id, s.name, s.student_code, CONCAT(c.name, " ", c.name_numeric, " ", sec.name) as class_name')
    ->from('student s')
    ->join('enroll e', 's.student_id = e.student_id')
    ->join('class c', 'e.class_id = c.class_id')
    ->join('section sec', 'e.section_id = sec.section_id')
    ->where('s.student_id', $assignment->student_id)
    ->limit(1)
    ->get()->row();
?>
<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.modern-form-group { margin-bottom: 28px; }
.modern-label { display: block; font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 12px; letter-spacing: 0.3px; }
.modern-label i { color: #667eea; margin-right: 8px; }
.modern-input { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.2s; background: #ffffff; color: #1a202c; font-weight: 500; }
.modern-input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); background: #f7fafc; }
.modern-input:disabled { background: #f3f4f6; cursor: not-allowed; }
.modern-btn { padding: 16px 32px; border-radius: 10px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.modern-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.student-info { background: #f8f9fa; padding: 16px; border-radius: 10px; border-left: 4px solid #667eea; margin-bottom: 24px; }
</style>

<?php echo form_open('admin/manage_discount_assignments/update', array('id' => 'edit_form_modal')); ?>
    <input type="hidden" name="assignment_id" value="<?php echo $assignment->assignment_id; ?>">
    
    <div class="student-info">
        <div style="font-weight: 600; color: #1a202c; margin-bottom: 4px;">
            <i class="fa fa-user"></i> <?php echo $student->name; ?>
        </div>
        <div style="font-size: 13px; color: #6b7280;">
            <?php echo $student->student_code; ?> • <?php echo $student->class_name; ?>
        </div>
    </div>

    <div class="modern-form-group">
        <label class="modern-label"><i class="fa fa-tag"></i><?php echo get_phrase('select_discount_profile'); ?></label>
        <div style="position: relative;">
            <input type="text" id="profile_search_edit" placeholder="<?php echo get_phrase('type_profile_name'); ?>" class="modern-input" autocomplete="off">
            <div id="profile_suggestions_edit" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
        </div>
        <div id="selected_profile_display_edit" style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 50px; padding: 12px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e2e8f0;"></div>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="modern-btn">
            <i class="fa fa-save"></i><?php echo get_phrase('update_assignment'); ?>
        </button>
    </div>
<?php echo form_close(); ?>

<script>
let allProfilesEdit = <?php echo json_encode($profiles); ?>;
let selectedProfileEdit = '<?php echo $assignment->profile_id; ?>';
let assignedProfileIds = <?php echo json_encode($assigned_profile_ids); ?>;

function initEditModal() {
    $('#profile_search_edit').val('');
    $('#profile_suggestions_edit').hide();
    updateProfileDisplayEdit();
    
    $('#profile_search_edit').off('click focus input');
    $('#profile_search_edit').on('click focus', function() {
        const filtered = allProfilesEdit.filter(p => String(p.profile_id) !== String(selectedProfileEdit) && !assignedProfileIds.includes(String(p.profile_id)));
        displayProfileSuggestionsEdit(filtered);
    });

    $('#profile_search_edit').on('input', function() {
        const searchTerm = $(this).val().trim();
        if(searchTerm.length < 1) {
            const filtered = allProfilesEdit.filter(p => String(p.profile_id) !== String(selectedProfileEdit) && !assignedProfileIds.includes(String(p.profile_id)));
            displayProfileSuggestionsEdit(filtered);
            return;
        }
        const filtered = allProfilesEdit.filter(p => 
            String(p.profile_id) !== String(selectedProfileEdit) && 
            !assignedProfileIds.includes(String(p.profile_id)) &&
            p.profile_name.toLowerCase().includes(searchTerm.toLowerCase())
        );
        displayProfileSuggestionsEdit(filtered);
    });
}

$(document).ready(function() {
    initEditModal();
});

$('#modal_ajax').on('shown.bs.modal', function() {
    setTimeout(function() {
        if($('#edit_form_modal').length) {
            initEditModal();
        }
    }, 100);
});

function displayProfileSuggestionsEdit(data) {
    if(data.length === 0) {
        $('#profile_suggestions_edit').html('<div style="padding: 16px; text-align: center; color: #94a3b8;">No profiles found</div>').show();
        return;
    }
    
    let html = '';
    data.forEach(profile => {
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
            <div class="profile-item-edit" data-id="${profile.profile_id}" style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.profile-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.profile-meta').style.color='#718096';">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                            <i class="fa fa-tag" style="margin-right: 6px; font-size: 12px;"></i>${profile.profile_name}
                        </div>
                        <div class="profile-meta" style="font-size: 12px; color: #718096;">
                            <span style="margin-right: 10px;"><i class="fa fa-${discountMethod === 'percentage' ? 'percent' : 'dollar'}" style="margin-right: 4px;"></i>${valueDisplay}</span>
                            <span style="margin-right: 10px;">${categoryBadge}</span>
                            <span>${typeDisplay}</span>
                        </div>
                    </div>
                    <i class="fa fa-check-circle" style="font-size: 20px; opacity: 0.5;"></i>
                </div>
            </div>
        `;
    });
    $('#profile_suggestions_edit').html(html).slideDown(200);
}

$(document).off('click', '.profile-item-edit').on('click', '.profile-item-edit', function() {
    selectedProfileEdit = String($(this).data('id'));
    updateProfileDisplayEdit();
    $('#profile_search_edit').val('');
    $('#profile_suggestions_edit').hide();
});

function updateProfileDisplayEdit() {
    const profile = allProfilesEdit.find(p => String(p.profile_id) === String(selectedProfileEdit));
    if(!profile) {
        $('#selected_profile_display_edit').html('<div style="color: #94a3b8; text-align: center; width: 100%; padding: 8px;"><i class="fa fa-info-circle"></i> No profile selected</div>');
        return;
    }
    
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
    
    $('#selected_profile_display_edit').html(`
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 10px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(245, 87, 108, 0.3); width: 100%;">
            <div style="flex: 1;">
                <div>${categoryBadge} ${profile.profile_name}</div>
                <div style="font-size: 11px; opacity: 0.9;">${typeDisplay} • ${valueDisplay}</div>
            </div>
        </div>
    `);
}

$(document).off('click.editDropdown').on('click.editDropdown', function(e) {
    if(!$(e.target).closest('#profile_search_edit, #profile_suggestions_edit').length) {
        $('#profile_suggestions_edit').hide();
    }
});

$('#edit_form_modal').submit(function(e) {
    e.preventDefault();
    
    if(!selectedProfileEdit) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_profile"); ?>', 'error');
        return;
    }
    
    $('.close').click();
    showAjaxModal_alert('<?php echo get_phrase("updating"); ?>...', 'loading');
    
    let postData = $(this).serialize() + '&profile_id=' + encodeURIComponent(selectedProfileEdit);
    
    // Debug logging
    console.log('Submitting edit form with data:', postData);
    console.log('Selected profile ID:', selectedProfileEdit);
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: postData,
        dataType: 'json'
    }).done(function(response) {
        console.log('Response:', response);
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
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
        showAjaxModal_alert('<?php echo get_phrase("an_error_occurred"); ?>', 'error');
    });
});
</script>
